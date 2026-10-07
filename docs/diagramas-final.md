# Diagramas finales de EDUQuest

Fecha de revision documental: 2026-10-07.

Los diagramas reflejan el estado tecnico actual del repositorio. El despliegue local esta verificado; el alojamiento definitivo sigue pendiente.

## Casos de uso principales

```mermaid
flowchart LR
    Admin[Administrador] --> CU01[Gestionar docentes]
    Docente[Docente] --> CU02[Gestionar clases y alumnos]
    Docente --> CU03[Crear y revisar borradores]
    Docente --> CU04[Elegir escenario ilustrado]
    Docente --> CU05[Publicar y asignar misiones]
    Docente --> CU06[Generar borrador con IA]
    Docente --> CU07[Consultar seguimiento]
    Alumno[Alumno] --> CU08[Recorrer mapa ilustrado]
    Alumno --> CU09[Completar actividades]
    Alumno --> CU10[Consultar avatar y tienda]
    CU06 --> CU03
    CU03 --> CU04
    CU04 --> CU05
    CU05 --> CU08
    CU08 --> CU09
    CU09 --> CU07
    CU09 --> CU10
```

## Flujo docente con escenario de mapa

```mermaid
sequenceDiagram
    actor T as Docente
    participant UI as Vue/Inertia
    participant C as TeacherMissionController
    participant M as Mission
    participant L as MissionLifecycle
    participant A as MissionAssignmentManager

    T->>UI: Crea o edita borrador
    UI->>C: POST/PATCH titulo, nivel, nodos y map_theme
    C->>M: Guarda draft con map_theme cerrado
    T->>UI: Revisa nodos y confirma revision
    UI->>L: Publicar borrador preparado
    L->>M: status=published, published_at
    T->>UI: Asigna a clase propia
    UI->>A: Crear asignacion e inscripciones
    A-->>UI: Asignacion abierta
```

## Generacion IA revisable

```mermaid
sequenceDiagram
    actor T as Docente
    participant UI as Formulario IA
    participant G as MissionDraftGenerator
    participant P as MissionDraftProvider
    participant M as Mission

    T->>UI: Solicita borrador
    UI->>G: Payload validado
    G->>P: Peticion controlada al proveedor
    P-->>G: JSON estructurado
    G->>M: Crea mision source=ai status=draft
    M-->>UI: Abre editor normal
    Note over UI,M: No publica ni asigna automaticamente
```

## Mapa ilustrado del alumno

```mermaid
flowchart TD
    A["Alumno autenticado"] --> B["Listado /student/missions"]
    B --> C["Inscripcion activa y asignacion abierta"]
    C --> D["Mapa /student/missions/{enrollment}"]
    D --> E{"missions.map_theme"}
    E --> F["fantasy_map.png"]
    E --> G["science_map.png"]
    E --> H["old_west_map.png"]
    D --> I["Nodos ordenados por position"]
    I --> J["Disponible"]
    I --> K["Bloqueado"]
    I --> L["Completado"]
    J --> M["Actividad"]
    M --> N["Progreso academico"]
    M --> O["XP y monedas si procede"]
```

## Modelo de datos resumido

```mermaid
erDiagram
    USERS ||--o{ CLASSROOMS : owns
    USERS ||--o{ CLASSROOM_MEMBERSHIPS : student
    CLASSROOMS ||--o{ CLASSROOM_MEMBERSHIPS : contains
    USERS ||--o{ MISSIONS : creates
    MISSIONS ||--o{ MISSION_NODES : contains
    MISSIONS ||--o{ MISSION_ASSIGNMENTS : assigned
    CLASSROOMS ||--o{ MISSION_ASSIGNMENTS : receives
    MISSION_ASSIGNMENTS ||--o{ MISSION_ENROLLMENTS : opens
    USERS ||--o{ MISSION_ENROLLMENTS : follows
    MISSION_ENROLLMENTS ||--o{ NODE_PROGRESS : records
    MISSION_NODES ||--o{ NODE_PROGRESS : completed
    MISSION_NODES ||--o{ QUIZ_QUESTIONS : has
    QUIZ_QUESTIONS ||--o{ QUIZ_OPTIONS : has
    MISSION_ENROLLMENTS ||--o{ QUIZ_ATTEMPTS : stores
    MISSION_NODES ||--o{ QUIZ_ATTEMPTS : grades
    QUIZ_ATTEMPTS ||--o{ QUIZ_ANSWERS : includes
    QUIZ_QUESTIONS ||--o{ QUIZ_ANSWERS : answered
    QUIZ_OPTIONS ||--o{ QUIZ_ANSWERS : selected
    MISSION_NODES ||--o{ FLASHCARDS : has
    USERS ||--|| AVATAR_PROFILES : owns
    MISSION_ASSIGNMENTS ||--o{ MISSION_ASSIGNMENT_NODE_REWARDS : snapshots
    MISSION_NODES ||--o{ MISSION_ASSIGNMENT_NODE_REWARDS : rewarded
    USERS ||--o{ STUDENT_REWARD_GRANTS : earns
    MISSION_NODES ||--o{ STUDENT_REWARD_GRANTS : grants
    NODE_PROGRESS ||--|| STUDENT_REWARD_GRANTS : first_completion
    USERS ||--o{ COIN_LEDGER_ENTRIES : moves
    STUDENT_REWARD_GRANTS ||--o| COIN_LEDGER_ENTRIES : credits
    COSMETIC_ITEMS ||--o{ STUDENT_COSMETIC_ITEMS : purchased
    USERS ||--o{ STUDENT_COSMETIC_ITEMS : owns
    COSMETIC_ITEMS ||--o{ COIN_LEDGER_ENTRIES : debits
    COSMETIC_ITEMS ||--o{ AVATAR_PROFILES : equipped

    MISSIONS {
        bigint id
        bigint teacher_id
        string status
        string source
        string map_theme
    }
```

## Despliegue y evidencias

```mermaid
flowchart LR
    Repo["Repositorio Git"] --> Docker["Docker Compose local"]
    Docker --> Laravel["Laravel Sail PHP 8.4"]
    Docker --> MySQL["MySQL 8.4"]
    Laravel --> Vite["Vite/Vue build"]
    Laravel --> Tests["Composer ci:check"]
    Tests --> Local["Localhost verificado"]
    Repo -. pendiente .-> Hosting["Alojamiento definitivo"]
    Hosting -. pendiente .-> Backups["Copias programadas"]
    Hosting -. pendiente .-> Dominio["Dominio/SSL"]
```

## Exportaciones validadas

Los bloques Mermaid de este documento se renderizaron el 2026-10-07 con Mermaid CLI `10.9.2` mediante `npx --package @mermaid-js/mermaid-cli` y Chrome local configurado para Puppeteer. Los PNG y SVG generados estan en `docs/evidencias/diagramas-final/`.

## Enlaces relacionados

- [Memoria borrador final](memoria-borrador-final.md)
- [Estado del hito final](estado-hito-final.md)
- [Revision final de recorridos](revision-final-recorridos.md)
- [RFTP](rftp.md)
