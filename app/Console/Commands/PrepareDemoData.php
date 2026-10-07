<?php

namespace App\Console\Commands;

use App\Enums\MissionAssignmentStatus;
use App\Enums\MissionMapTheme;
use App\Enums\MissionNodeType;
use App\Enums\MissionSource;
use App\Enums\MissionStatus;
use App\Enums\UserRole;
use App\Enums\VideoProvider;
use App\Models\AvatarProfile;
use App\Models\Classroom;
use App\Models\ClassroomMembership;
use App\Models\Mission;
use App\Models\MissionAssignmentNodeReward;
use App\Models\MissionNode;
use App\Models\StudentCosmeticItem;
use App\Models\User;
use App\Services\AvatarShopService;
use App\Services\MissionAssignmentManager;
use App\Services\MissionEnrollmentSynchronizer;
use App\Services\MissionLifecycle;
use App\Services\MissionReadiness;
use App\Services\StudentRewardService;
use Database\Seeders\CosmeticCatalogSeeder;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class PrepareDemoData extends Command
{
    private const TEACHER_USERNAME = 'carlinchis';

    private const STUDENT_USERNAME = 'alumno_demo';

    private const CLASSROOM_NAME = 'Clase demo EDUQuest';

    private const MISSION_TITLE = 'Exploradores del sistema solar';

    /** @var list<string> */
    private const ADDITIONAL_MISSION_TITLES = [
        'Guardianes del ciclo del agua',
        'Detectives de los ecosistemas',
        'Viaje al interior de la Tierra',
        'Laboratorio de la materia',
    ];

    /** @var array<string, MissionMapTheme> */
    private const MISSION_MAP_THEMES = [
        self::MISSION_TITLE => MissionMapTheme::Science,
        'Guardianes del ciclo del agua' => MissionMapTheme::Fantasy,
        'Detectives de los ecosistemas' => MissionMapTheme::OldWest,
        'Viaje al interior de la Tierra' => MissionMapTheme::Science,
        'Laboratorio de la materia' => MissionMapTheme::Fantasy,
    ];

    protected $signature = 'eduquest:prepare-demo
        {--password-env=EDUQUEST_DEMO_STUDENT_PASSWORD : Local environment variable containing the student password}
        {--reset-student-password : Replace the password of the existing demo student}';

    protected $description = 'Prepare idempotent local demonstration data for EDUQuest';

    public function handle(
        MissionLifecycle $lifecycle,
        MissionAssignmentManager $assignments,
        MissionEnrollmentSynchronizer $enrollments,
        MissionReadiness $readiness,
        AvatarShopService $avatarShop,
        StudentRewardService $rewards,
    ): int {
        if (! app()->environment(['local', 'testing'])) {
            $this->components->error('Demo data can only be prepared in local or testing environments.');

            return self::FAILURE;
        }

        try {
            $teacher = $this->teacher();
            app(CosmeticCatalogSeeder::class)->run();
            $existingStudent = User::query()->where('username', self::STUDENT_USERNAME)->first();
            $studentPassword = $existingStudent === null || $this->option('reset-student-password')
                ? $this->studentPassword()
                : null;

            [$classroom, $student, $membership, $mission] = DB::transaction(
                fn (): array => $this->prepareCoreData($teacher, $studentPassword),
            );
            $missions = new Collection([$mission]);
            $missions = $missions->merge(DB::transaction(
                fn (): Collection => $this->prepareAdditionalMissions($teacher),
            ));

            if ($studentPassword !== null && ! $student->wasRecentlyCreated) {
                $student->forceFill([
                    'password' => $studentPassword,
                    'must_change_password' => false,
                ])->save();
            }

            if (! $student->active) {
                $student->forceFill(['active' => true])->save();
            }

            $this->prepareAvatar($student, $avatarShop);

            $missionAssignments = collect();
            foreach ($missions as $preparedMission) {
                if ($preparedMission->status === MissionStatus::Draft) {
                    $lifecycle->publish($preparedMission);
                    $preparedMission->refresh();
                }

                $assignment = $preparedMission->assignments()
                    ->where('classroom_id', $classroom->id)
                    ->first();

                if ($assignment === null) {
                    $assignment = $assignments->assign($preparedMission, collect([$classroom]))->sole();
                } elseif ($assignment->status !== MissionAssignmentStatus::Open) {
                    throw ValidationException::withMessages([
                        'assignment' => "La asignacion demo de {$preparedMission->title} no esta abierta.",
                    ]);
                }

                $missionAssignments->push($assignment);
            }

            $enrollments->sync($membership->refresh());
            $demoEnrollments = $student->missionEnrollments()
                ->whereIn('assignment_id', $missionAssignments->pluck('id'))
                ->get();
            $allReady = $missions->every(
                fn (Mission $preparedMission): bool => $readiness->check($preparedMission->refresh())['ready'],
            );
            $rewardSummary = $rewards->summary($student->refresh());
            $availableExperience = (int) MissionAssignmentNodeReward::query()
                ->whereIn('assignment_id', $missionAssignments->pluck('id'))
                ->sum('experience_reward');
            $availableCoins = (int) MissionAssignmentNodeReward::query()
                ->whereIn('assignment_id', $missionAssignments->pluck('id'))
                ->sum('coin_reward');

            $this->components->info('Datos de demostracion preparados correctamente.');
            $this->table(['Dato', 'Valor'], [
                ['Docente', "{$teacher->username} (ID {$teacher->id})"],
                ['Clase', "{$classroom->name} (ID {$classroom->id})"],
                ['Alumno', "{$student->username} (ID {$student->id})"],
                ['Misiones', (string) $missions->count()],
                ['Actividades disponibles', (string) $missions->sum(fn (Mission $item): int => $item->nodes()->count())],
                ['XP disponible', (string) $availableExperience],
                ['Monedas disponibles', (string) $availableCoins],
                ['Asignaciones abiertas', (string) $missionAssignments->count()],
                ['Inscripciones activas', (string) $demoEnrollments->where('active', true)->count()],
                ['Preparadas', $allReady ? 'yes' : 'no'],
                ['Progreso conservado', (string) $demoEnrollments->sum(fn ($item): int => $item->progress()->count())],
                ['XP', (string) $rewardSummary['experience']],
                ['Monedas', (string) $rewardSummary['coins']],
                ['Compras', (string) $student->cosmeticItems()
                    ->where('acquisition_type', StudentCosmeticItem::ACQUISITION_PURCHASE)
                    ->count()],
            ]);

            return self::SUCCESS;
        } catch (ValidationException $exception) {
            $this->components->error($exception->validator->errors()->first());

            return self::FAILURE;
        }
    }

    /** @return Collection<int, Mission> */
    private function prepareAdditionalMissions(User $teacher): Collection
    {
        return new Collection(array_map(function (string $title) use ($teacher): Mission {
            $mission = Mission::query()
                ->where('teacher_id', $teacher->id)
                ->where('title', $title)
                ->first();

            if ($mission !== null) {
                $this->assertAdditionalMission($mission);
                $this->ensureDemoMapTheme($mission);

                return $mission;
            }

            $mission = new Mission([
                'title' => $title,
                'description' => $this->additionalMissionDescription($title),
                'subject' => 'Ciencias Naturales',
                'level' => '5.º de Primaria',
                'map_theme' => self::MISSION_MAP_THEMES[$title],
            ]);
            $mission->status = MissionStatus::Draft;
            $mission->source = MissionSource::Manual;
            $mission->teacher()->associate($teacher);
            $mission->save();
            $this->createAdditionalMissionNodes($mission);

            return $mission;
        }, self::ADDITIONAL_MISSION_TITLES));
    }

    private function additionalMissionDescription(string $title): string
    {
        return match ($title) {
            'Guardianes del ciclo del agua' => 'Una ruta breve por los cambios de estado y el recorrido continuo del agua en la naturaleza.',
            'Detectives de los ecosistemas' => 'Una investigacion sobre seres vivos, habitat y relaciones dentro de un ecosistema.',
            'Viaje al interior de la Tierra' => 'Una expedicion para reconocer las capas principales de nuestro planeta.',
            'Laboratorio de la materia' => 'Una practica guiada para distinguir propiedades y estados de la materia.',
            default => throw new \LogicException("Mision demo no definida: {$title}"),
        };
    }

    private function createAdditionalMissionNodes(Mission $mission): void
    {
        match ($mission->title) {
            'Guardianes del ciclo del agua' => $this->createScienceNodes(
                $mission,
                'El viaje del agua',
                'El agua circula continuamente entre la superficie y la atmosfera. Se evapora, se condensa en nubes y vuelve mediante la precipitacion.',
                [['Evaporacion', 'Paso del agua liquida a vapor.'], ['Condensacion', 'Formacion de gotas al enfriarse el vapor.']],
                '¿Que proceso forma las nubes al enfriarse el vapor de agua?',
                [['Condensacion', true], ['Evaporacion', false], ['Fusion', false]],
                'La condensacion transforma el vapor en pequenas gotas que forman las nubes.',
                'Cuidamos el agua',
                'El agua dulce disponible es limitada. Cerrar el grifo y evitar contaminar rios ayuda a conservarla.',
            ),
            'Detectives de los ecosistemas' => $this->createScienceNodes(
                $mission,
                'Pistas de un ecosistema',
                'Un ecosistema incluye los seres vivos, el medio fisico y las relaciones que mantienen entre si.',
                [['Productor', 'Ser vivo que fabrica su propio alimento.'], ['Consumidor', 'Ser vivo que obtiene energia alimentandose de otros.']],
                '¿Cual de estos seres vivos es un productor?',
                [['Una encina', true], ['Un zorro', false], ['Un aguila', false]],
                'Las plantas producen su alimento mediante la fotosintesis.',
                'Equilibrio natural',
                'Los cambios en una poblacion pueden afectar a toda la red alimentaria del ecosistema.',
            ),
            'Viaje al interior de la Tierra' => $this->createScienceNodes(
                $mission,
                'Las capas del planeta',
                'La Tierra se organiza en corteza, manto y nucleo. La corteza es la capa exterior y la mas delgada.',
                [['Corteza', 'Capa exterior solida.'], ['Manto', 'Capa situada entre la corteza y el nucleo.']],
                '¿En que capa vivimos?',
                [['Corteza', true], ['Nucleo externo', false], ['Nucleo interno', false]],
                'Vivimos sobre la corteza terrestre, la capa mas externa.',
                'Un planeta activo',
                'El calor interno de la Tierra participa en procesos como el movimiento de las placas y el vulcanismo.',
            ),
            'Laboratorio de la materia' => $this->createScienceNodes(
                $mission,
                'Materia a nuestro alrededor',
                'La materia tiene masa y ocupa un lugar. Puede presentarse en estado solido, liquido o gaseoso.',
                [['Masa', 'Cantidad de materia de un cuerpo.'], ['Volumen', 'Espacio que ocupa un cuerpo.']],
                '¿Que estado mantiene forma y volumen propios?',
                [['Solido', true], ['Liquido', false], ['Gas', false]],
                'Los solidos conservan su forma y su volumen en condiciones normales.',
                'Cambios reversibles',
                'Algunos cambios de estado pueden invertirse: el hielo se funde y el agua puede volver a congelarse.',
            ),
            default => throw new \LogicException("Nodos demo no definidos: {$mission->title}"),
        };
    }

    /**
     * @param  list<array{0: string, 1: string}>  $cards
     * @param  list<array{0: string, 1: bool}>  $options
     */
    private function createScienceNodes(
        Mission $mission,
        string $openingTitle,
        string $openingBody,
        array $cards,
        string $questionText,
        array $options,
        string $explanation,
        string $closingTitle,
        string $closingBody,
    ): void {
        $mission->nodes()->create([
            'position' => 1,
            'type' => MissionNodeType::Explanation,
            'title' => $openingTitle,
            'body' => $openingBody,
            'coin_reward' => 2,
        ]);

        $flashcards = $mission->nodes()->create([
            'position' => 2,
            'type' => MissionNodeType::Flashcards,
            'title' => 'Conceptos clave',
            'coin_reward' => 2,
        ]);
        $flashcards->flashcards()->createMany(array_map(
            fn (array $card, int $position): array => [
                'position' => $position + 1,
                'front' => $card[0],
                'back' => $card[1],
            ],
            $cards,
            array_keys($cards),
        ));

        $quiz = $mission->nodes()->create([
            'position' => 3,
            'type' => MissionNodeType::Quiz,
            'title' => 'Comprueba lo aprendido',
            'pass_threshold' => 70,
            'coin_reward' => 3,
        ]);
        $question = $quiz->questions()->create([
            'position' => 1,
            'statement' => $questionText,
            'explanation' => $explanation,
        ]);
        foreach ($options as $position => [$text, $correct]) {
            $question->options()->create([
                'position' => $position + 1,
                'text' => $text,
                'is_correct' => $correct,
            ]);
        }

        $mission->nodes()->create([
            'position' => 4,
            'type' => MissionNodeType::Explanation,
            'title' => $closingTitle,
            'body' => $closingBody,
            'coin_reward' => 2,
        ]);
    }

    private function assertAdditionalMission(Mission $mission): void
    {
        $types = $mission->nodes()->orderBy('position')->get(['type'])->map(
            fn (MissionNode $node): string => $node->type->value,
        )->all();

        if ($mission->source !== MissionSource::Manual
            || $mission->status === MissionStatus::Archived
            || $types !== ['explanation', 'flashcards', 'quiz', 'explanation']) {
            throw ValidationException::withMessages([
                'mission' => "La mision demo {$mission->title} no coincide con la estructura esperada.",
            ]);
        }
    }

    private function teacher(): User
    {
        $teacher = User::query()->where('username', self::TEACHER_USERNAME)->first();

        if ($teacher === null || ! $teacher->isTeacher() || ! $teacher->active) {
            throw ValidationException::withMessages([
                'teacher' => 'No existe un docente activo con el usuario carlinchis.',
            ]);
        }

        return $teacher;
    }

    /** @return array{Classroom, User, ClassroomMembership, Mission} */
    private function prepareCoreData(User $teacher, ?string $studentPassword): array
    {
        $classroom = Classroom::query()
            ->where('teacher_id', $teacher->id)
            ->where('name', self::CLASSROOM_NAME)
            ->first();

        if ($classroom === null) {
            $classroom = new Classroom([
                'name' => self::CLASSROOM_NAME,
                'level' => '5.º de Primaria',
                'subject' => 'Ciencias Naturales',
            ]);
            $classroom->teacher()->associate($teacher);
            $classroom->save();
        }

        $student = User::query()->where('username', self::STUDENT_USERNAME)->first();

        if ($student === null) {
            $student = new User([
                'name' => 'Alumno Demo',
                'username' => self::STUDENT_USERNAME,
                'email' => null,
                'password' => $studentPassword,
            ]);
            $student->role = UserRole::Student;
            $student->active = true;
            $student->must_change_password = false;
            $student->creator()->associate($teacher);
            $student->save();
        } elseif (! $student->isStudent()
            || $student->name !== 'Alumno Demo'
            || $student->created_by !== $teacher->id) {
            throw ValidationException::withMessages([
                'student' => 'El usuario alumno_demo ya existe y no corresponde al alumno demo de Carlinchis.',
            ]);
        }

        $membership = $classroom->memberships()->firstOrCreate(
            ['student_id' => $student->id],
            ['active' => true, 'activated_at' => now()],
        );

        if (! $membership->active) {
            $membership->forceFill([
                'active' => true,
                'activated_at' => now(),
                'deactivated_at' => null,
            ])->save();
        }

        $mission = Mission::query()
            ->where('teacher_id', $teacher->id)
            ->where('title', self::MISSION_TITLE)
            ->first();

        if ($mission === null) {
            $mission = new Mission([
                'title' => self::MISSION_TITLE,
                'description' => 'Una expedicion guiada para conocer el sistema solar y reconocer sus principales cuerpos celestes.',
                'subject' => 'Ciencias Naturales',
                'level' => '5.º de Primaria',
                'map_theme' => self::MISSION_MAP_THEMES[self::MISSION_TITLE],
            ]);
            $mission->status = MissionStatus::Draft;
            $mission->source = MissionSource::Manual;
            $mission->teacher()->associate($teacher);
            $mission->save();
            $this->createMissionNodes($mission);
        } else {
            $this->assertDemoMission($mission);
            $this->ensureDemoMapTheme($mission);
        }

        return [$classroom, $student, $membership, $mission];
    }

    private function createMissionNodes(Mission $mission): void
    {
        $mission->nodes()->create([
            'position' => 1,
            'type' => MissionNodeType::Explanation,
            'title' => 'Nuestro vecindario cosmico',
            'body' => 'El sistema solar esta formado por el Sol y los cuerpos que giran a su alrededor. Los ocho planetas siguen orbitas, y tambien existen planetas enanos, satelites, asteroides y cometas.',
            'coin_reward' => 2,
        ]);

        $mission->nodes()->create([
            'position' => 2,
            'type' => MissionNodeType::Video,
            'title' => 'Viaje visual por el sistema solar',
            'video_provider' => VideoProvider::YouTube,
            'video_id' => 'libKVRa01L8',
            'coin_reward' => 2,
        ]);

        $quiz = $mission->nodes()->create([
            'position' => 3,
            'type' => MissionNodeType::Quiz,
            'title' => 'Comprueba la ruta',
            'pass_threshold' => 70,
            'coin_reward' => 3,
        ]);
        $this->createQuiz($quiz);

        $flashcards = $mission->nodes()->create([
            'position' => 4,
            'type' => MissionNodeType::Flashcards,
            'title' => 'Bitacora planetaria',
            'coin_reward' => 2,
        ]);
        $flashcards->flashcards()->createMany([
            ['position' => 1, 'front' => 'Mercurio', 'back' => 'El planeta mas cercano al Sol.'],
            ['position' => 2, 'front' => 'Tierra', 'back' => 'Nuestro planeta, con agua liquida abundante.'],
            ['position' => 3, 'front' => 'Jupiter', 'back' => 'El planeta mas grande del sistema solar.'],
            ['position' => 4, 'front' => 'Neptuno', 'back' => 'El planeta mas alejado del Sol.'],
        ]);
    }

    private function createQuiz(MissionNode $quiz): void
    {
        $questions = [
            [
                'statement' => '¿Que astro ocupa el centro del sistema solar?',
                'explanation' => 'El Sol es la estrella alrededor de la que orbitan los planetas.',
                'options' => [['La Tierra', false], ['El Sol', true], ['La Luna', false]],
            ],
            [
                'statement' => '¿Cual es el planeta mas grande?',
                'explanation' => 'Jupiter es el planeta de mayor tamano del sistema solar.',
                'options' => [['Marte', false], ['Jupiter', true], ['Venus', false]],
            ],
            [
                'statement' => '¿Que movimiento realiza un planeta alrededor del Sol?',
                'explanation' => 'La traslacion es el movimiento orbital de un planeta alrededor del Sol.',
                'options' => [['Traslacion', true], ['Evaporacion', false], ['Condensacion', false]],
            ],
        ];

        foreach ($questions as $questionPosition => $questionData) {
            $question = $quiz->questions()->create([
                'position' => $questionPosition + 1,
                'statement' => $questionData['statement'],
                'explanation' => $questionData['explanation'],
            ]);

            foreach ($questionData['options'] as $optionPosition => [$text, $correct]) {
                $question->options()->create([
                    'position' => $optionPosition + 1,
                    'text' => $text,
                    'is_correct' => $correct,
                ]);
            }
        }
    }

    private function assertDemoMission(Mission $mission): void
    {
        $types = $mission->nodes()->orderBy('position')->get(['type'])->map(
            fn (MissionNode $node): string => $node->type->value,
        )->all();

        if ($mission->source !== MissionSource::Manual
            || $mission->status === MissionStatus::Archived
            || $types !== ['explanation', 'video', 'quiz', 'flashcards']) {
            throw ValidationException::withMessages([
                'mission' => 'Ya existe una mision con el titulo demo, pero no coincide con la estructura esperada.',
            ]);
        }
    }

    private function ensureDemoMapTheme(Mission $mission): void
    {
        $theme = self::MISSION_MAP_THEMES[$mission->title] ?? null;

        if ($theme !== null && $mission->map_theme !== $theme) {
            $mission->forceFill(['map_theme' => $theme])->save();
        }
    }

    private function prepareAvatar(User $student, AvatarShopService $avatarShop): void
    {
        if ($student->avatarProfile()->exists()) {
            return;
        }

        DB::transaction(function () use ($student, $avatarShop): void {
            $profile = new AvatarProfile([
                'character_key' => 'character-a',
                'setup_completed_at' => now(),
            ]);
            $profile->student()->associate($student);
            $profile->save();
            $avatarShop->grantStarter($student, $profile);
        });
    }

    private function studentPassword(): string
    {
        $environmentName = (string) $this->option('password-env');
        $environmentValue = Env::get($environmentName);
        $password = is_string($environmentValue) ? $environmentValue : '';
        $confirmation = $password;

        if ($password === '') {
            $password = (string) $this->secret('Contrasena para Alumno Demo');
            $confirmation = (string) $this->secret('Confirma la contrasena');
        }

        $validator = Validator::make([
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $password;
    }
}
