param(
    [string]$OutputPath = "docs\EDUQuest_memoria_final_trabajo.docx",
    [switch]$ReviewInWord
)

$ErrorActionPreference = "Stop"

function X([string]$s) {
    if ($null -eq $s) { return "" }
    $mojibakeMarker = "[$([char]0x00C3)$([char]0x00C2)]"
    if ($s -match $mojibakeMarker) {
        $s = [System.Text.Encoding]::UTF8.GetString([System.Text.Encoding]::GetEncoding(1252).GetBytes($s))
    }
    $escaped = [System.Security.SecurityElement]::Escape($s)
    $sb = New-Object System.Text.StringBuilder
    foreach ($ch in $escaped.ToCharArray()) {
        $code = [int][char]$ch
        if ($code -gt 127) {
            [void]$sb.Append(("&#x{0:X4};" -f $code))
        } else {
            [void]$sb.Append($ch)
        }
    }
    return $sb.ToString()
}

function P([string]$text, [string]$style = "Normal") {
    $st = if ($style -ne "Normal") { "<w:pPr><w:pStyle w:val=`"$style`"/></w:pPr>" } else { "" }
    return "<w:p>$st<w:r><w:t xml:space=`"preserve`">$(X $text)</w:t></w:r></w:p>"
}

function Toc() {
    return "<w:p><w:r><w:fldChar w:fldCharType=`"begin`"/></w:r><w:r><w:instrText xml:space=`"preserve`">TOC \o `"1-3`" \h \z \u</w:instrText></w:r><w:r><w:fldChar w:fldCharType=`"separate`"/></w:r><w:r><w:t xml:space=`"preserve`">Indice pendiente de actualizar automaticamente en Word.</w:t></w:r><w:r><w:fldChar w:fldCharType=`"end`"/></w:r></w:p>"
}

function Bullet([string]$text) {
    return "<w:p><w:pPr><w:pStyle w:val=`"ListParagraph`"/><w:numPr><w:ilvl w:val=`"0`"/><w:numId w:val=`"$script:CurrentListId`"/></w:numPr></w:pPr><w:r><w:t xml:space=`"preserve`">$(X $text)</w:t></w:r></w:p>"
}

function Start-List() {
    $script:CurrentListId += 1
}

function PageBreak() {
    return "<w:p><w:r><w:br w:type=`"page`"/></w:r></w:p>"
}

function Table([string[]]$headers, [object[]]$rows) {
    $widths = switch ($headers.Count) {
        2 { @(6100,2926) }
        3 { @(1600,3300,4126) }
        4 { if ($headers[0] -eq 'RFTP') { @(800,2100,2100,4026) } else { @(1800,2300,1700,3226) } }
        5 { @(3000,1500,1500,1500,1526) }
        default { throw 'Unsupported table geometry' }
    }
    $xml = "<w:tbl><w:tblPr><w:tblStyle w:val=`"TableGrid`"/><w:tblW w:w=`"0`" w:type=`"auto`"/><w:tblBorders><w:top w:val=`"single`" w:sz=`"4`" w:space=`"0`" w:color=`"D0D7DE`"/><w:left w:val=`"single`" w:sz=`"4`" w:space=`"0`" w:color=`"D0D7DE`"/><w:bottom w:val=`"single`" w:sz=`"4`" w:space=`"0`" w:color=`"D0D7DE`"/><w:right w:val=`"single`" w:sz=`"4`" w:space=`"0`" w:color=`"D0D7DE`"/><w:insideH w:val=`"single`" w:sz=`"4`" w:space=`"0`" w:color=`"D0D7DE`"/><w:insideV w:val=`"single`" w:sz=`"4`" w:space=`"0`" w:color=`"D0D7DE`"/></w:tblBorders></w:tblPr>"
    $xml = $xml.Replace('<w:tblW w:w="0" w:type="auto"/>', '<w:tblW w:w="9026" w:type="dxa"/><w:tblLayout w:type="fixed"/><w:tblCellMar><w:top w:w="70" w:type="dxa"/><w:left w:w="90" w:type="dxa"/><w:bottom w:w="70" w:type="dxa"/><w:right w:w="90" w:type="dxa"/></w:tblCellMar>')
    $xml += '<w:tblGrid>'
    foreach ($width in $widths) { $xml += "<w:gridCol w:w=`"$width`"/>" }
    $xml += '</w:tblGrid>'
    $xml += "<w:tr><w:trPr><w:tblHeader/><w:cantSplit/></w:trPr>"
    $column = 0
    foreach ($h in $headers) {
        $width = $widths[$column++]
        $xml += "<w:tc><w:tcPr><w:tcW w:w=`"$width`" w:type=`"dxa`"/><w:shd w:fill=`"EAF2F8`"/></w:tcPr><w:p><w:pPr><w:keepNext/><w:spacing w:after=`"40`"/></w:pPr><w:r><w:rPr><w:b/><w:sz w:val=`"20`"/></w:rPr><w:t>$(X $h)</w:t></w:r></w:p></w:tc>"
    }
    $xml += "</w:tr>"
    foreach ($row in $rows) {
        $xml += "<w:tr><w:trPr><w:cantSplit/></w:trPr>"
        $column = 0
        foreach ($cell in $row) {
            $width = $widths[$column++]
            $xml += "<w:tc><w:tcPr><w:tcW w:w=`"$width`" w:type=`"dxa`"/></w:tcPr><w:p><w:pPr><w:spacing w:after=`"40`"/></w:pPr><w:r><w:rPr><w:sz w:val=`"20`"/></w:rPr><w:t xml:space=`"preserve`">$(X ([string]$cell))</w:t></w:r></w:p></w:tc>"
        }
        $xml += "</w:tr>"
    }
    $xml += "</w:tbl>"
    return $xml
}

function TableWithCaption([string]$caption, [string[]]$headers, [object[]]$rows) {
    $script:TableCounter += 1
    $captionXml = (P "Tabla $($script:TableCounter). $caption" "Caption").Replace('<w:pPr>', '<w:pPr><w:keepNext/>')
    return $captionXml + (Table $headers $rows)
}

function AddImageXml([string]$path, [string]$caption, [ref]$rels, [ref]$mediaCopies, [ref]$ridCounter) {
    if (-not (Test-Path -LiteralPath $path)) { return "" }
    $script:FigureCounter += 1
    Add-Type -AssemblyName System.Drawing
    $img = [System.Drawing.Image]::FromFile((Resolve-Path -LiteralPath $path))
    $wpx = $img.Width
    $hpx = $img.Height
    $img.Dispose()
    $maxCx = 5486400
    $cx = [int64]($wpx * 9525)
    $cy = [int64]($hpx * 9525)
    if ($cx -gt $maxCx) {
        $ratio = $cy / $cx
        $cx = $maxCx
        $cy = [int64]($cx * $ratio)
    }
    $rid = "rId$($ridCounter.Value)"
    $maxCy = if ($path -match 'final-mapa-(ciencia|oeste)-ui-') { 7086600 } else { 5486400 }
    if ($cy -gt $maxCy) {
        $cx = [int64]($cx * ($maxCy / $cy))
        $cy = $maxCy
    }
    $name = "image$($ridCounter.Value).png"
    $ridCounter.Value++
    $rels.Value += "<Relationship Id=`"$rid`" Type=`"http://schemas.openxmlformats.org/officeDocument/2006/relationships/image`" Target=`"media/$name`"/>"
    $mediaCopies.Value += ,@($path, $name)
    $xml = @"
<w:p><w:r><w:drawing><wp:inline distT="0" distB="0" distL="0" distR="0" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing"><wp:extent cx="$cx" cy="$cy"/><wp:docPr id="$($ridCounter.Value)" name="$(X $name)"/><a:graphic xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:pic xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture"><pic:nvPicPr><pic:cNvPr id="0" name="$(X $name)"/><pic:cNvPicPr/></pic:nvPicPr><pic:blipFill><a:blip r:embed="$rid"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill><pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="$cx" cy="$cy"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr></pic:pic></a:graphicData></a:graphic></wp:inline></w:drawing></w:r></w:p>
"@
    $xml = $xml.Replace('<w:p><w:r><w:drawing>', '<w:p><w:pPr><w:keepNext/><w:jc w:val="center"/></w:pPr><w:r><w:drawing>')
    $xml += P "Figura $($script:FigureCounter). $caption" "Caption"
    return $xml
}

$outFull = if ([System.IO.Path]::IsPathRooted($OutputPath)) { $OutputPath } else { Join-Path (Get-Location) $OutputPath }
# El DOCX versionado es la fuente editorial: conservar las aportaciones hechas en Word.
# La construcción OOXML inicial solo se usa cuando todavía no existe el documento.
if (-not (Test-Path -LiteralPath $outFull)) {
$tmp = Join-Path (Get-Location) "tmp\memoria-ooxml"
if (Test-Path -LiteralPath $tmp) {
    $resolvedTmp = (Resolve-Path -LiteralPath $tmp).Path
    $workspacePrefix = (Get-Location).Path.TrimEnd('\') + '\'
    if (-not $resolvedTmp.StartsWith($workspacePrefix, [StringComparison]::OrdinalIgnoreCase)) { throw "Unsafe temporary directory" }
    Remove-Item -LiteralPath $resolvedTmp -Recurse -Force
}
New-Item -ItemType Directory -Force -Path (Join-Path $tmp "_rels"), (Join-Path $tmp "word\_rels"), (Join-Path $tmp "word\media"), (Join-Path $tmp "docProps") | Out-Null

$rels = ""
$media = @()
$ridCounter = 1
$script:CurrentListId = 0
$script:FigureCounter = 0
$script:TableCounter = 0
$body = ""
$Aacute = [string][char]0x00C1
$Iacute = [string][char]0x00CD
$Oacute = [string][char]0x00D3
$Ntilde = [string][char]0x00D1

$body += P "EduQuest" "Title"
$body += P "Memoria final de trabajo del proyecto de Desarrollo de Aplicaciones Web" "Subtitle"
$body += P "Producto: EduQuest"
$body += P "Centro: The Power"
$body += P "Proyecto: Desarrollo de Aplicaciones Web"
$body += P "Curso / modalidad: 2.º de DAW, modalidad online"
$body += P "Autores: Gonzalo Arranz y David García"
$body += P "Tutora: Susana Roldán"
$body += P "Fecha de entrega: PENDIENTE DE COMPLETAR POR LOS AUTORES"
$body += P "Estado: borrador final de trabajo. No declara entrega, aprobación académica ni alojamiento permanente."
$body += P "Estructura basada en la plantilla académica original: portada, índices, abstract, justificación, introducción, objetivos/RFTP, descripción, diseños, tecnología, metodología, trabajos futuros, conclusiones y referencias."
$body += PageBreak
$body += P "DEDICATORIA (OPCIONAL)" "Heading1"
$body += P "PENDIENTE DE COMPLETAR POR GONZALO ARRANZ Y DAVID GARCÍA."
$body += PageBreak
$body += P ("${Iacute}NDICES") "Heading1"
$body += P "Índice de contenido"
$body += Toc
$body += PageBreak
$body += P "ABSTRACT" "Heading1"
$body += P "Resumen en español" "Heading2"
$body += P "EduQuest es una aplicación web educativa desarrollada como proyecto de Desarrollo de Aplicaciones Web por Gonzalo Arranz y David García en The Power, dentro de 2.º de DAW en modalidad online. Permite al profesorado convertir repasos en misiones visuales, asignarlas a clases y consultar el avance del alumnado. El alumno recorre un mapa secuencial, completa actividades de explicación, vídeo, cuestionario y flashcards, y conserva su progreso entre sesiones. La solución se ha desarrollado como monolito Laravel con Vue, Inertia y MySQL, aplicando sesiones, CSRF, validación de servidor y Policies. La generación asistida por IA crea borradores revisables por el docente y nunca publica ni asigna contenido automáticamente. R09 se documenta como ampliación posterior con avatar, XP, monedas y tienda cosmética separados de la evaluación académica."
$body += P "Abstract in English" "Heading2"
$body += P "EduQuest is an educational web application developed by Gonzalo Arranz and David García as a Web Application Development project at The Power, in the second year of DAW online. It lets teachers turn review activities into visual missions, assign them to classes and monitor student progress. Students follow a sequential map, complete explanation, video, quiz and flashcard activities, and keep their progress across sessions. The solution is a Laravel monolith with Vue, Inertia and MySQL, using sessions, CSRF protection, server-side validation and Policies. AI-assisted generation creates teacher-reviewable drafts and never publishes or assigns content automatically. R09 is documented as a later extension with avatars, XP, coins and a cosmetic shop kept separate from academic assessment."

$body += P ("JUSTIFICACI${Oacute}N DEL PROYECTO") "Heading1"
$body += P "El proyecto responde a la necesidad de preparar repasos estructurados y atractivos sin convertir el alcance en un videojuego completo. EduQuest adapta la idea de misiones visuales y progresión por etapas a una aplicación propia, acotada y reproducible para el proyecto de Desarrollo de Aplicaciones Web."
$body += P "El valor no se presenta como invención absoluta: Moodle ya ofrece liberación condicional y existen propuestas comerciales de gamificación educativa. EduQuest concentra el flujo en el docente que crea o genera un borrador, revisa, publica, asigna y consulta progreso, y en el alumno que avanza por un mapa lineal."
$body += P "La demo usa datos ficticios. Cualquier uso en un centro real exigiría revisar protección de datos, cuentas, copias y permisos según las normas del centro."

$body += P ("INTRODUCCI${Oacute}N") "Heading1"
$body += P "EduQuest organiza clases y alumnos, prepara misiones de repaso, controla el avance secuencial y ofrece seguimiento docente sin mezclar permisos. El registro público está cerrado y las altas son controladas."
$body += P "El recorrido principal es: administrador crea docentes; docente crea clases y alumnos, prepara una misión manual o con ayuda de IA, revisa sus nodos, publica y asigna; alumno abre el mapa, completa actividades disponibles y desbloquea la siguiente; docente consulta seguimiento."

$body += P "OBJETIVOS" "Heading1"
Start-List
foreach ($b in @(
    "Acceso por usuario, sesiones, CSRF, contraseñas protegidas y separación de roles.",
    "Clases, alumnos, matrículas activas e histórico.",
    "Misiones con borrador, nodos, validación, publicación, duplicado, archivo y asignación.",
    "Actividades de explicación, vídeo, cuestionario y flashcards con progreso persistente.",
    "Seguimiento docente de avance, puntos, intentos y mejor nota.",
    "Generación asistida como borrador revisable, nunca como publicación automática.",
    "Instalación, pruebas, datos de demo, copia/restauración y preparación de despliegue.",
    "R09 como ampliación posterior, separada de la evaluación académica."
)) { $body += Bullet $b }
$body += TableWithCaption "Estado RFTP actualizado." @("RFTP","Descripción","Estado real","Evidencia") @(
    @("R01","Acceso, roles y permisos","Completo","Policies, middleware, Fortify, RoleAccessTest"),
    @("R02","Clases y alumnos","Completo","ClassroomManagementTest, ClassroomPolicy"),
    @("R03","Misiones manuales, publicación y asignación","Completo","MissionDraftManagementTest, MissionPublishingAndAssignmentTest"),
    @("R04","Actividades del alumno","Completo","StudentMissionJourneyTest, StudentQuizAttemptTest"),
    @("R05","Avance secuencial y progreso","Completo; mapas como ampliación visual","StudentMissionAccess, NodeProgressService, map_theme"),
    @("R06","Seguimiento docente","Completo","TeacherTrackingController, TeacherTrackingTest"),
    @("R07","Generación asistida revisable","Completo en alcance implementado","MissionDraftGenerator, OpenAiMissionDraftProvider, AiMissionGenerationTest"),
    @("R08","Instalación, pruebas, documentación y evidencias","Parcial","README, R08F02, revision-final; faltan fecha de entrega, horas por autor, reparto de tareas, capturas finales y cierre"),
    @("R09","Avatar, XP, monedas y tienda cosmética","Completo como ampliación posterior","StudentRewardService, AvatarShopService, R09 concurrencia")
)

$body += P ("DESCRIPCI${Oacute}N") "Heading1"
$body += P "Arquitectura de la solución" "Heading2"
$body += P "La aplicación es un monolito modular Laravel 13 con PHP 8.4 en Docker, MySQL 8.4, Eloquent, Form Requests, Policies y servicios de dominio. El frontend usa Vue 3, TypeScript, Inertia, Tailwind CSS y componentes shadcn-vue/reka-ui. No hay API REST separada ni JWT: se trabaja bajo el mismo origen con sesiones y CSRF."
$body += AddImageXml "docs\evidencias\diagramas-final\06-despliegue-y-evidencias.png" "Despliegue local, pruebas y evidencias documentadas." ([ref]$rels) ([ref]$media) ([ref]$ridCounter)
$body += P "Casos de uso principales" "Heading2"
$body += AddImageXml "docs\evidencias\diagramas-final\01-casos-de-uso-principales.png" "Casos de uso principales." ([ref]$rels) ([ref]$media) ([ref]$ridCounter)
$body += TableWithCaption "Casos de uso principales." @("Caso","Descripción","Tablas","Clases / interfaces") @(
    @("CU01 Acceder","Cuenta activa entra y accede a su panel.","users, sessions","Login.vue, DashboardController"),
    @("CU02 Gestionar clase","Docente crea clase, alumnos y matrículas.","classrooms, memberships","TeacherClassroomController"),
    @("CU03 Crear misión manual","Docente prepara borrador y escenario.","missions, nodes, quiz, flashcards","TeacherMissionController, MissionNodeWriter"),
    @("CU04 Generar borrador IA","Docente solicita borrador estructurado revisable.","ai_generations, missions","AiMissionGenerationController"),
    @("CU05 Publicar y asignar","Misión preparada se asigna a clases propias.","assignments, enrollments","MissionLifecycle, MissionAssignmentManager"),
    @("CU06 Completar actividad","Alumno completa nodo disponible y guarda progreso.","node_progress, quiz_attempts","StudentMissionController, NodeProgressService"),
    @("CU07 Consultar seguimiento","Docente revisa avance e intentos.","progress, attempts","TeacherTrackingController"),
    @("CU08 Administrar docentes","Administrador crea o desactiva docentes.","users","AdminTeacherController")
)

$body += P ("DISE${Ntilde}OS") "Heading1"
$body += P "Los diagramas finales se exportaron a PNG/SVG y existen en docs/evidencias/diagramas-final. Se insertan solo imágenes existentes."
foreach ($img in @(
    @("docs\evidencias\diagramas-final\02-flujo-docente-con-escenario-de-mapa.png","Flujo docente con escenario de mapa."),
    @("docs\evidencias\diagramas-final\03-generacion-ia-revisable.png","Generación IA revisable."),
    @("docs\evidencias\diagramas-final\04-mapa-ilustrado-del-alumno.png","Mapa ilustrado del alumno."),
    @("docs\evidencias\diagramas-final\05-modelo-de-datos-resumido.png","Modelo de datos resumido.")
)) { $body += AddImageXml $img[0] $img[1] ([ref]$rels) ([ref]$media) ([ref]$ridCounter) }
$body += PageBreak
$body += P "Interfaces y evidencias finales" "Heading2"
$body += P "Se incorporan la landing y las vistas de fantasía aportadas por los autores, junto con cuatro capturas autenticadas realizadas el 5 de noviembre de 2026 de las misiones de revisión de ciencia ficción y oeste. Cada escenario se comprobó en escritorio (1440 px de ancho) y móvil emulado (390 px), con cuatro nodos visibles y sin desbordamiento horizontal. Las imágenes completas se conservan en docs/evidencias/final para consultarlas a su resolución original."
foreach ($img in @(
    @("docs\evidencias\final\final-landing-escritorio.png","Landing pública en escritorio: identidad, acceso y enlace al tráiler."),
    @("docs\evidencias\final\final-mapa-fantasia-escritorio.png","Mapa de fantasía en escritorio, vista parcial: ruta y actividades completadas. La cabecera y la parte superior del mapa quedan fuera del encuadre."),
    @("docs\evidencias\final\final-mapa-fantasia-movil.png","Mapa de fantasía en formato móvil, vista parcial: progreso al 0 %, primera etapa disponible y etapas bloqueadas. El encuadre no acredita la pantalla completa ni un ancho concreto del dispositivo."),
    @("docs\evidencias\final\final-mapa-ciencia-ui-desktop.png","Ciencia ficción en escritorio (1440 px): misión de revisión, progreso al 0 %, ruta y cuatro nodos; el primero disponible y los restantes bloqueados."),
    @("docs\evidencias\final\final-mapa-ciencia-ui-mobile.png","Ciencia ficción en móvil emulado (390 px): mapa, cuatro nodos, progreso y lista de actividades sin desbordamiento horizontal."),
    @("docs\evidencias\final\final-mapa-oeste-ui-desktop.png","Oeste en escritorio (1440 px): misión de revisión, progreso al 0 %, ruta y cuatro nodos; el primero disponible y los restantes bloqueados."),
    @("docs\evidencias\final\final-mapa-oeste-ui-mobile.png","Oeste en móvil emulado (390 px): mapa, cuatro nodos, progreso y lista de actividades sin desbordamiento horizontal.")
)) {
    $body += AddImageXml $img[0] $img[1] ([ref]$rels) ([ref]$media) ([ref]$ridCounter)
    $body += PageBreak
}
$body += P "Evidencias reutilizadas del hito 80" "Heading2"
$body += P "Se conservan las capturas existentes del formulario IA, cuestionario, seguimiento y tienda como evidencias del hito 80. Las capturas antiguas de mapas en lista se sustituyen por el material visual final."
foreach ($img in @(
    @("docs\evidencias\hito-80\formulario_ia.png","Captura existente hito 80 local: formulario de IA."),
    @("docs\evidencias\hito-80\alumno_mission_quiz.png","Captura existente hito 80 local: cuestionario."),
    @("docs\evidencias\hito-80\tracking.png","Captura existente hito 80 local: seguimiento docente."),
    @("docs\evidencias\hito-80\alumno_avatar_upgarde.png","Captura existente hito 80 local: avatar/tienda.")
)) { $body += AddImageXml $img[0] $img[1] ([ref]$rels) ([ref]$media) ([ref]$ridCounter) }

$body += P ("TECNOLOG${Iacute}A") "Heading1"
$body += TableWithCaption "Tecnología empleada." @("Área","Tecnología","Uso") @(
    @("Servidor","PHP 8.4 en Docker y Laravel 13","Rutas, validación, sesiones, Policies, Eloquent y servicios"),
    @("Interfaz","Vue 3, TypeScript, Inertia","Páginas docente/alumno sin API REST separada"),
    @("Estilo","Tailwind CSS, shadcn-vue/reka-ui, lucide","Componentes y diseño adaptable"),
    @("Base de datos","MySQL 8.4","Usuarios, clases, misiones, progreso, IA, recompensas"),
    @("Pruebas","Pest, PHPStan, Pint, vue-tsc, vp check","Permisos, dominio, formato y análisis estático"),
    @("IA","OpenAI Responses API y Structured Outputs","Borradores revisables con gpt-5.4-mini configurable"),
    @("Despliegue","Docker Compose local y compose.prod.yml","Producción aislada local; no alojamiento permanente"),
    @("Acceso temporal","Cloudflare Quick Tunnel","Solo acceso temporal; no despliegue definitivo")
)

$body += P ("METODOLOG${Iacute}A") "Heading1"
$body += P "Hemos trabajado de forma iterativa por hitos: propuesta, hito 50, hito 80 y preparación final. Cada avance se contrasta con RFTP, pruebas y documentos de estado. El plan inicial contempla 200 horas para R01-R08. El registro de sesiones no contiene duraciones; por tanto, Git permite contrastar cambios y secuencia, pero no calcular horas reales ni atribuir contribuciones personales."
$body += P "El anexo A desarrolla una estimación retrospectiva orientativa de 240 horas y un reparto propuesto entre los autores. Es un escenario de trabajo pendiente de validar, no un registro de horas realizadas. También recoge las desviaciones de alcance verificables y la declaración propuesta de uso de IA."
$body += P "Comprobaciones técnicas registradas en docs/revision-final.md: composer ci:check correcto con vp check, vue-tsc, Pint, PHPStan y Pest; Pest registró 145 pruebas superadas, 1258 aserciones y 9 omitidas; npm run build completo con 3409 módulos transformados."
$body += P "R08F02 documentó instalación limpia aislada, catálogo, datos de demo ficticios, copia MySQL y restauración en otra base. El despliegue definitivo, dominio, HTTPS real y copias programadas siguen pendientes."
$body += P "Cloudflare Quick Tunnel se documentó como acceso temporal desde el ordenador de desarrollo. La URL cambia, caduca y deja de funcionar al cerrar cloudflared; no equivale a alojamiento permanente."

$body += P "TRABAJOS FUTUROS" "Heading1"
Start-List
foreach ($b in @(
    "Completar despliegue externo real con VM, dominio, HTTPS, firewall y backups probados.",
    "Ampliar las comprobaciones visuales a dispositivos físicos y completar las revisiones académicas pendientes.",
    "Ampliar R09 con más apariencias o piezas por capas solo si se autoriza como nueva fase.",
    "Definir proveedor de correo real y política de recuperación para un entorno no local.",
    "Evaluar con alumnado real solo tras resolver permisos, datos y consentimiento del centro."
)) { $body += Bullet $b }

$body += P "CONCLUSIONES" "Heading1"
$body += P "EduQuest queda documentado como aplicación funcional en local, con separación de roles, misiones, progreso, seguimiento, generación asistida revisable y ampliación cosmética R09. Las pruebas y documentos actuales permiten explicar decisiones técnicas, reglas de servidor y límites."
$body += P "La decisión de mantener un monolito ha permitido reunir autenticación, autorización, persistencia y vistas bajo un mismo origen. El avance por nodos exige comprobar reglas en servidor: ocultar un botón no protege una actividad bloqueada. Las pruebas de acceso y de idempotencia permiten comprobar que el progreso y las recompensas respetan esas reglas."
$body += P "La generación asistida aporta un punto de partida para preparar contenido, pero su integración exige validar estructura, controlar errores y conservar la revisión docente antes de publicar. La experiencia visual se amplía con mapas ilustrados y recompensas cosméticas; estas ampliaciones no demuestran por sí mismas una mejora del aprendizaje. Esa hipótesis requeriría una evaluación específica con alumnado."
$body += P "Como balance técnico, el proyecto integra competencias de desarrollo web, diseño de datos, pruebas y documentación en un flujo educativo demostrable. La reflexión personal conjunta propuesta se incluye en el anexo A para revisión de ambos autores. Permanecen pendientes la fecha de entrega, la validación de horas y contribuciones individuales, las evidencias visuales indicadas y las condiciones académicas de cierre."

$body += P "REFERENCIAS" "Heading1"
$body += TableWithCaption "Referencias y uso en el proyecto." @("Referencia","Uso") @(
    @("Laravel. (s. f.). Release Notes. Recuperado de https://laravel.com/docs/13.x/releases","Laravel 13 y compatibilidad del servidor."),
    @("Laravel. (s. f.). Starter Kits. Recuperado de https://laravel.com/docs/13.x/starter-kits","Autenticación base, Vue e Inertia."),
    @("Inertia.js. (s. f.). Inertia.js. Recuperado de https://inertiajs.com/","Decisión de monolito con páginas Inertia."),
    @("Oracle. (s. f.). MySQL 8.4 Reference Manual. Recuperado de https://dev.mysql.com/doc/refman/8.4/en/","Persistencia, transacciones y restricciones."),
    @("Moodle. (s. f.). Restrict access settings. Recuperado de https://docs.moodle.org/en/Restrict_access_settings","Comparativa de liberación condicional."),
    @("HMH. (s. f.). HMH Classcraft. Recuperado de https://www.hmhco.com/programs/classcraft","Antecedente de misiones educativas gamificadas."),
    @("OpenAI. (s. f.). Structured Outputs. Recuperado de https://developers.openai.com/api/docs/guides/structured-outputs","Salida JSON estructurada para R07."),
    @("OpenAI. (s. f.). Modelos de API. Recuperado de https://developers.openai.com/api/docs/models/gpt-5.4-mini","Modelo documentado para generación asistida.")
)

$body += (P "ANEXO A. PLANIFICACIÓN, AUTORÍA Y USO DE IA" "Heading1").Replace('<w:pPr>', '<w:pPr><w:pageBreakBefore/>')
$body += P "A.1. Horas por RFTP: estimación pendiente de validación" "Heading2"
$body += P "ESTIMACIÓN ORIENTATIVA, NO HORAS REALES ACREDITADAS. Los valores siguientes se proponen por la complejidad de los bloques implementados. La distribución individual es hipotética y requiere validación de Gonzalo Arranz y David García con sus registros. Las 200 horas iniciales son una referencia global del plan, no 200 horas exigidas a cada autor."
$body += TableWithCaption "Escenario orientativo de esfuerzo por autor; todos los valores estimados." @("RFTP","Plan inicial (h)","Gonzalo (h est.)","David (h est.)","Total est. (h)") @(
    @("R01 Acceso y permisos","16","8","8","16"),
    @("R02 Clases y alumnos","12","6","6","12"),
    @("R03 Misiones","20","12","10","22"),
    @("R04 Actividades","24","10","14","24"),
    @("R05 Progreso y mapas","24","16","14","30"),
    @("R06 Seguimiento","12","6","6","12"),
    @("R07 IA revisable","24","10","16","26"),
    @("R08 Integración y documentación","68","30","36","66"),
    @("R09 Ampliación cosmética","Fuera del plan","18","14","32"),
    @("TOTAL","200","116","124","240")
)
$body += P "HORAS REALES PENDIENTES: sustituir o validar esta tabla con sesiones, duración, tarea RFTP y evidencia; completar total real de Gonzalo Arranz, total real de David García y total conjunto. No trasladar las cifras estimadas a docs/registro-horas.md como trabajo realizado."
$body += P "A.2. Reparto de tareas propuesto" "Heading2"
$body += P "PROPUESTA PARA VALIDAR ENTRE AMBOS AUTORES. Gonzalo Arranz: interfaz Vue/Inertia, presentación de actividades, mapas, avatar y catálogo visual; revisión de capturas y presentación. David García: servicios Laravel, autorización, persistencia, integración IA, pruebas de dominio e instalación/copia/restauración. Trabajo conjunto propuesto: análisis RFTP, modelo de datos, integración, revisión de reglas, memoria y preparación de demo."
$body += P "ATRIBUCIÓN REAL PENDIENTE: confirmar responsables y colaboración por bloque con ambos autores. El reparto anterior organiza una propuesta coherente con el producto; no demuestra quién ejecutó cada tarea ni sustituye la validación de contribuciones."
$body += P "A.3. Planificación y desviaciones" "Heading2"
$body += P "El plan original distribuye el esfuerzo acumulado en 20 horas para propuesta, 100 para el hito 50, 160 para el hito 80 y 200 para la preparación final. Son referencias de planificación. La secuencia documental y Git permiten reconstruir el avance funcional, pero no fijar duraciones de cada fase ni fechas oficiales de entrega."
$body += P "Las desviaciones de alcance comprobables son la incorporación posterior de R09 (avatar, XP, monedas y tienda cosmética) y la evolución del mapa secuencial hacia tres escenarios ilustrados. La documentación también recoge correcciones de formato y validación, pruebas de concurrencia, ensayo de producción aislada y acceso temporal mediante Quick Tunnel. El despliegue externo permanente continúa pendiente."
$body += P "En el escenario estimado, R01-R08 suman 208 horas frente a las 200 iniciales (+8 h), y R09 añade 32 horas fuera del alcance original. El total orientativo de 240 horas supone +40 h (+20 %). Estos cálculos describen únicamente la propuesta estimada; la desviación real en horas y calendario queda pendiente de registros y fechas confirmadas."
$body += P "A.4. Declaración propuesta sobre uso de IA" "Heading2"
$body += P "BORRADOR PARA REVISIÓN DE LOS AUTORES Y LA TUTORA. La documentación del proyecto recoge asistencia de Codex en tareas de desarrollo y documentación, así como generación de recursos visuales. Debe completarse el inventario de herramientas, recursos y uso concreto. Los resultados asistidos se contrastan con el código, las pruebas y las fuentes disponibles; la responsabilidad de revisar la versión académica corresponde a los autores."
$body += P "El producto incorpora además una función distinta: generación de borradores educativos desde el servidor mediante un proveedor IA. Las salidas se validan y requieren revisión docente; no se publican ni asignan automáticamente. Las credenciales se conservan fuera del repositorio y las evidencias públicas evitan datos privados."
$body += P "NORMA DEL CENTRO PENDIENTE DE CONFIRMACIÓN: no consta una formulación oficial de The Power en la documentación consultada. Susana Roldán debe confirmar el formato de declaración, las restricciones y cualquier requisito de cita o anexo. Este texto es una declaración propuesta, no una política oficial ni una autorización académica."
$body += P "A.5. Reflexión personal conjunta propuesta" "Heading2"
$body += P "BORRADOR EN PRIMERA PERSONA PLURAL, PENDIENTE DE VALIDACIÓN DE AMBOS AUTORES. Con EduQuest hemos conectado las distintas capas de una aplicación web alrededor de un recorrido educativo: preparar una misión, asignarla y consultar el avance. Consideramos especialmente relevante que la lógica de acceso y progreso se mantenga en el servidor y pueda comprobarse mediante pruebas."
$body += P "El proyecto nos permite reflexionar sobre la relación entre alcance y calidad. Los mapas y la personalización enriquecen la experiencia, pero añaden revisión visual y reglas que deben mantenerse separadas de la evaluación académica. La generación con IA requiere el mismo criterio: puede facilitar un borrador, mientras la revisión de contenido sigue siendo imprescindible."
$body += P "Como continuidad, priorizaríamos cerrar las evidencias móviles, contrastar la experiencia con usuarios y validar las condiciones de despliegue. Antes de adoptar esta reflexión como personal, ambos autores deben confirmar que representa su experiencia y añadir, si procede, aprendizajes individuales o dificultades concretas."
$body += P "A.6. Datos que requieren confirmación" "Heading2"
Start-List
foreach ($b in @("Fecha de entrega: portada.","Horas reales y totales por autor: A.1 y metodología.","Contribuciones personales: A.2.","Duraciones y desviaciones reales: A.3.","Inventario de uso IA y norma académica de The Power: A.4.","Reflexión personal conjunta: validar A.5 con ambos autores.","Dedicatoria opcional: completar o retirar la página correspondiente.")) { $body += Bullet $b }
$body += P "ANEXO B. EVIDENCIAS VISUALES FINALES" "Heading1"
Start-List
foreach ($b in @("Landing y fantasía: imágenes aportadas por los autores, incorporadas con su encuadre original.","Ciencia ficción: /student/missions/11, cuenta ficticia revision_final_alumno; capturas de escritorio y móvil emulado realizadas.","Oeste: /student/missions/12, misma cuenta ficticia; capturas de escritorio y móvil emulado realizadas.")) { $body += Bullet $b }
$body += P "No quedan pendientes las cuatro capturas de mapas solicitadas. Las misiones ya existían en la base local y no fue necesario crear contenido ni llamar a un proveedor IA. La sesión temporal usada para las capturas se revocó al finalizar. La comprobación de móvil corresponde a emulación de navegador a 390 px, no a una prueba en dispositivo físico. Las ilustraciones originales de ciencia ficción y oeste se conservan como recursos de diseño separados."

$body += "<w:sectPr><w:pgSz w:w=`"11906`" w:h=`"16838`"/><w:pgMar w:top=`"1440`" w:right=`"1440`" w:bottom=`"1440`" w:left=`"1440`" w:header=`"708`" w:footer=`"708`" w:gutter=`"0`"/></w:sectPr>"

$documentXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:document xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" xmlns:cx="http://schemas.microsoft.com/office/drawing/2014/chartex" xmlns:cx1="http://schemas.microsoft.com/office/drawing/2015/9/8/chartex" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:w10="urn:schemas-microsoft-com:office:word" xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" xmlns:w15="http://schemas.microsoft.com/office/word/2012/wordml" xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" mc:Ignorable="w14 w15 wp14"><w:body>$body</w:body></w:document>
"@

$stylesXml = @"
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="22"/></w:rPr><w:pPr><w:spacing w:after="120"/></w:pPr></w:style><w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:rPr><w:b/><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="52"/><w:color w:val="1F4E79"/></w:rPr><w:pPr><w:spacing w:after="240"/></w:pPr></w:style><w:style w:type="paragraph" w:styleId="Subtitle"><w:name w:val="Subtitle"/><w:rPr><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="28"/><w:color w:val="666666"/></w:rPr></w:style><w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:qFormat/><w:rPr><w:b/><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="32"/><w:color w:val="1F4E79"/></w:rPr><w:pPr><w:keepNext/><w:spacing w:before="360" w:after="160"/></w:pPr></w:style><w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/><w:qFormat/><w:rPr><w:b/><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="26"/><w:color w:val="2F75B5"/></w:rPr><w:pPr><w:keepNext/><w:spacing w:before="240" w:after="120"/></w:pPr></w:style><w:style w:type="paragraph" w:styleId="Caption"><w:name w:val="caption"/><w:rPr><w:i/><w:rFonts w:ascii="Arial" w:hAnsi="Arial"/><w:sz w:val="18"/><w:color w:val="555555"/></w:rPr><w:pPr><w:spacing w:after="160"/></w:pPr></w:style><w:style w:type="paragraph" w:styleId="ListParagraph"><w:name w:val="List Paragraph"/><w:basedOn w:val="Normal"/><w:pPr><w:ind w:left="720" w:hanging="360"/></w:pPr></w:style><w:style w:type="table" w:styleId="TableGrid"><w:name w:val="Table Grid"/><w:tblPr><w:tblBorders><w:top w:val="single" w:sz="4" w:color="D0D7DE"/><w:left w:val="single" w:sz="4" w:color="D0D7DE"/><w:bottom w:val="single" w:sz="4" w:color="D0D7DE"/><w:right w:val="single" w:sz="4" w:color="D0D7DE"/><w:insideH w:val="single" w:sz="4" w:color="D0D7DE"/><w:insideV w:val="single" w:sz="4" w:color="D0D7DE"/></w:tblBorders></w:tblPr></w:style></w:styles>
"@

$numberingNums = ""
for ($numId = 1; $numId -le [Math]::Max(8, $script:CurrentListId); $numId++) {
    $numberingNums += "<w:num w:numId=`"$numId`"><w:abstractNumId w:val=`"0`"/><w:lvlOverride w:ilvl=`"0`"><w:startOverride w:val=`"1`"/></w:lvlOverride></w:num>"
}
$numberingXml = "<?xml version=`"1.0`" encoding=`"UTF-8`" standalone=`"yes`"?><w:numbering xmlns:w=`"http://schemas.openxmlformats.org/wordprocessingml/2006/main`"><w:abstractNum w:abstractNumId=`"0`"><w:lvl w:ilvl=`"0`"><w:start w:val=`"1`"/><w:numFmt w:val=`"decimal`"/><w:lvlText w:val=`"%1.`"/><w:lvlJc w:val=`"left`"/><w:pPr><w:ind w:left=`"720`" w:hanging=`"360`"/></w:pPr><w:rPr><w:rFonts w:ascii=`"Arial`" w:hAnsi=`"Arial`"/></w:rPr></w:lvl></w:abstractNum>$numberingNums</w:numbering>"

Set-Content -LiteralPath (Join-Path $tmp "word\document.xml") -Value $documentXml -Encoding UTF8
Set-Content -LiteralPath (Join-Path $tmp "word\styles.xml") -Value $stylesXml -Encoding UTF8
Set-Content -LiteralPath (Join-Path $tmp "word\numbering.xml") -Value $numberingXml -Encoding UTF8

foreach ($m in $media) {
    Copy-Item -LiteralPath $m[0] -Destination (Join-Path $tmp "word\media\$($m[1])") -Force
}

Set-Content -LiteralPath (Join-Path $tmp "[Content_Types].xml") -Value "<?xml version=`"1.0`" encoding=`"UTF-8`" standalone=`"yes`"?><Types xmlns=`"http://schemas.openxmlformats.org/package/2006/content-types`"><Default Extension=`"rels`" ContentType=`"application/vnd.openxmlformats-package.relationships+xml`"/><Default Extension=`"xml`" ContentType=`"application/xml`"/><Default Extension=`"png`" ContentType=`"image/png`"/><Override PartName=`"/word/document.xml`" ContentType=`"application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml`"/><Override PartName=`"/word/styles.xml`" ContentType=`"application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml`"/><Override PartName=`"/word/numbering.xml`" ContentType=`"application/vnd.openxmlformats-officedocument.wordprocessingml.numbering+xml`"/><Override PartName=`"/docProps/core.xml`" ContentType=`"application/vnd.openxmlformats-package.core-properties+xml`"/><Override PartName=`"/docProps/app.xml`" ContentType=`"application/vnd.openxmlformats-officedocument.extended-properties+xml`"/></Types>" -Encoding UTF8
Set-Content -LiteralPath (Join-Path $tmp "_rels\.rels") -Value "<?xml version=`"1.0`" encoding=`"UTF-8`" standalone=`"yes`"?><Relationships xmlns=`"http://schemas.openxmlformats.org/package/2006/relationships`"><Relationship Id=`"rId1`" Type=`"http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument`" Target=`"word/document.xml`"/><Relationship Id=`"rId2`" Type=`"http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties`" Target=`"docProps/core.xml`"/><Relationship Id=`"rId3`" Type=`"http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties`" Target=`"docProps/app.xml`"/></Relationships>" -Encoding UTF8
Set-Content -LiteralPath (Join-Path $tmp "word\_rels\document.xml.rels") -Value "<?xml version=`"1.0`" encoding=`"UTF-8`" standalone=`"yes`"?><Relationships xmlns=`"http://schemas.openxmlformats.org/package/2006/relationships`"><Relationship Id=`"rStyles`" Type=`"http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles`" Target=`"styles.xml`"/><Relationship Id=`"rNumbering`" Type=`"http://schemas.openxmlformats.org/officeDocument/2006/relationships/numbering`" Target=`"numbering.xml`"/>$rels</Relationships>" -Encoding UTF8
Set-Content -LiteralPath (Join-Path $tmp "docProps\core.xml") -Value "<?xml version=`"1.0`" encoding=`"UTF-8`" standalone=`"yes`"?><cp:coreProperties xmlns:cp=`"http://schemas.openxmlformats.org/package/2006/metadata/core-properties`" xmlns:dc=`"http://purl.org/dc/elements/1.1/`" xmlns:dcterms=`"http://purl.org/dc/terms/`" xmlns:dcmitype=`"http://purl.org/dc/dcmitype/`" xmlns:xsi=`"http://www.w3.org/2001/XMLSchema-instance`"><dc:title>EduQuest memoria final de trabajo</dc:title><dc:creator>Gonzalo Arranz y David Garcia</dc:creator><cp:lastModifiedBy>Codex</cp:lastModifiedBy></cp:coreProperties>" -Encoding UTF8
Set-Content -LiteralPath (Join-Path $tmp "docProps\app.xml") -Value "<?xml version=`"1.0`" encoding=`"UTF-8`" standalone=`"yes`"?><Properties xmlns=`"http://schemas.openxmlformats.org/officeDocument/2006/extended-properties`" xmlns:vt=`"http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes`"><Application>Codex OOXML builder</Application></Properties>" -Encoding UTF8

if (Test-Path -LiteralPath $outFull) { Remove-Item -LiteralPath $outFull -Force }
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
$fs = [System.IO.File]::Open($outFull, [System.IO.FileMode]::CreateNew)
$archive = New-Object System.IO.Compression.ZipArchive($fs, [System.IO.Compression.ZipArchiveMode]::Create)
try {
    $files = Get-ChildItem -LiteralPath $tmp -Recurse -File
    foreach ($file in $files) {
        $rel = $file.FullName.Substring($tmp.Length).TrimStart([char[]]@([char]92, [char]47)).Replace([char]92, [char]47)
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($archive, $file.FullName, $rel) | Out-Null
    }
}
finally {
    $archive.Dispose()
    $fs.Dispose()
}
}
Write-Output "DOCX=$outFull"
if ($ReviewInWord) {
    Add-Type -AssemblyName System.Drawing
    $reviewPath = Join-Path (Get-Location) 'tmp\memoria-review-20261106'
    New-Item -ItemType Directory -Force -Path $reviewPath | Out-Null
    Get-ChildItem -LiteralPath $reviewPath -Filter 'page-*.png' -File | ForEach-Object { Remove-Item -LiteralPath $_.FullName -Force }
    Add-Type -AssemblyName Microsoft.VisualBasic
    $ownWord = $false
    try { $word = [Microsoft.VisualBasic.Interaction]::GetObject('', 'Word.Application') }
    catch { $word = New-Object -ComObject Word.Application; $ownWord = $true; $word.Visible = $false }
    $previousAlerts = $word.DisplayAlerts
    $word.DisplayAlerts = 0
    $doc = $null
    $ownDocument = $false
    try {
        foreach ($openDocument in $word.Documents) {
            if ($openDocument.FullName -eq $outFull) { $doc = $openDocument; break }
        }
        if ($null -eq $doc) { $doc = $word.Documents.Open($outFull, $false, $false); $ownDocument = $true }
        if ($doc.ReadOnly) { throw 'El documento está abierto en modo de solo lectura; no se puede guardar la revisión.' }
        $doc.Save()
        $backup = Join-Path $reviewPath 'antes-revision.docx'
        $inputStream = [IO.File]::Open($outFull, [IO.FileMode]::Open, [IO.FileAccess]::Read, [IO.FileShare]::ReadWrite)
        $outputStream = [IO.File]::Create($backup)
        try { $inputStream.CopyTo($outputStream) } finally { $inputStream.Dispose(); $outputStream.Dispose() }
        $replacements = [ordered]@{
            'A.4. Declaración propuesta sobre uso de IA' = 'A.4. Declaración de uso de inteligencia artificial'
            'BORRADOR PARA REVISIÓN DE LOS AUTORES Y LA TUTORA.' = 'DECLARACIÓN REDACTADA PARA VALIDACIÓN DE AMBOS AUTORES. En la elaboración de EduQuest se ha utilizado Codex como asistencia para proponer y revisar código, analizar incidencias, preparar documentación y comprobar la coherencia entre la memoria y los archivos del proyecto. En esta revisión también se ha empleado para redactar una propuesta de declaración de IA y de conclusiones conjuntas. La documentación recoge generación asistida de recursos visuales; no se atribuye a todos los recursos la misma herramienta ni se presupone que todas las imágenes sean generadas. La dedicatoria y la imagen de portada de esta versión han sido incorporadas por los autores al Word.'
            'El producto incorpora además una función distinta:' = 'Debe distinguirse esa asistencia durante el desarrollo de la funcionalidad R07 de la aplicación: EduQuest integra un proveedor OpenAI desde el servidor para generar borradores educativos con salida estructurada. El sistema valida su estructura y exige revisión docente antes de publicar; no publica ni asigna automáticamente. Las pruebas automatizadas de esta integración utilizan respuestas simuladas. La documentación registra una llamada real controlada anterior; redactar esta declaración no ha requerido nuevas llamadas al proveedor.'
            'NORMA DEL CENTRO PENDIENTE DE CONFIRMACIÓN:' = 'La asistencia de IA no acredita por sí misma la corrección del código, la calidad pedagógica ni la veracidad de una afirmación. La revisión se apoya en el código, las pruebas, la documentación y las evidencias disponibles. No se presentan como hechos las horas estimadas, las contribuciones individuales sin confirmar ni resultados de aprendizaje no evaluados. Las credenciales y los datos de sesión se mantienen fuera de Git. Corresponde a los autores revisar y asumir el texto que finalmente presenten. NORMA DEL CENTRO PENDIENTE DE CONFIRMACIÓN: no consta una formulación oficial de The Power en la documentación consultada; esta declaración deberá adaptarse si la tutora Susana Roldán indica un formato, inventario o requisito adicional. No constituye una autorización académica.'
            'A.5. Reflexión personal conjunta propuesta' = 'A.5. Conclusiones personales conjuntas propuestas'
            'BORRADOR EN PRIMERA PERSONA PLURAL,' = 'PROPUESTA EN PRIMERA PERSONA PLURAL, PENDIENTE DE VALIDACIÓN DE GONZALO ARRANZ Y DAVID GARCÍA. Con EduQuest hemos reunido análisis de requisitos, diseño de datos, desarrollo de servidor e interfaz, pruebas y documentación en un recorrido completo: un docente prepara y asigna una misión y un alumno avanza por sus actividades. Nuestra reflexión principal es que la coherencia entre estas partes importa tanto como cada pantalla por separado. Una interfaz debe reflejar las reglas que el servidor comprueba y los datos que realmente se conservan.'
            'El proyecto nos permite reflexionar' = 'Consideramos que las decisiones de alcance han sido esenciales. La progresión secuencial permite explicar y comprobar el recorrido; los mapas de fantasía, ciencia ficción y oeste aportan una presentación distinta sin cambiar sus reglas. La personalización de R09 amplía la experiencia y se mantiene separada de la evaluación académica. Esta evolución también muestra que cada ampliación exige revisar permisos, persistencia, pruebas y presentación en varios tamaños de pantalla.'
            'Como continuidad, priorizaríamos' = 'El uso de IA nos lleva a distinguir entre obtener una propuesta y contar con un resultado revisado. El contenido generado necesita validación y criterio docente, y el código y los textos asistidos también requieren contraste. Las capturas y las pruebas documentan comportamientos concretos, pero no demuestran por sí solas una mejora del aprendizaje. Como continuidad, priorizaríamos evaluar la aplicación con usuarios, ampliar la comprobación en dispositivos físicos y concretar un alojamiento permanente. Estas conclusiones conjuntas no atribuyen tareas, horas ni experiencias individuales no confirmadas; ambos autores deben validar que expresan su valoración antes de presentarlas.'
            'Inventario de uso IA y norma académica de The Power: A.4.' = 'Declaración de IA: validar el inventario descrito y confirmar la norma académica de The Power en A.4.'
            'Reflexión personal conjunta: validar A.5 con ambos autores.' = 'Conclusiones personales conjuntas: validar el texto de A.5 con ambos autores.'
            'README, R08F02, revision-final; faltan' = 'README, R08F02 y revisión final: capturas incorporadas; pendientes fecha de entrega, horas reales por autor, reparto validado y cierre académico.'
            'Como balance técnico, el proyecto integra' = 'Como balance técnico, el proyecto integra desarrollo web, diseño de datos, pruebas y documentación en un flujo educativo demostrable. Las conclusiones personales propuestas en A.5 requieren validación de ambos autores. Quedan pendientes fecha de entrega, horas reales, contribuciones y cierre académico. Las capturas de mapas solicitadas están incorporadas.'
        }
        for ($i = $doc.Paragraphs.Count; $i -ge 1; $i--) {
            $paragraph = $doc.Paragraphs.Item($i)
            $text = $paragraph.Range.Text.Trim()
            if ($text -eq 'ANEXO B. EVIDENCIAS VISUALES FINALES') { $paragraph.Format.PageBreakBefore = -1 }
            if ($text -eq 'Dedicatoria opcional: completar o retirar la página correspondiente.') { $paragraph.Range.Delete() | Out-Null; continue }
            foreach ($prefix in $replacements.Keys) {
                if ($text.StartsWith($prefix, [StringComparison]::Ordinal)) {
                    $range = $paragraph.Range.Duplicate
                    $range.End -= 1
                    $range.Text = $replacements[$prefix]
                    break
                }
            }
        }
        $search = $doc.Content.Duplicate
        $search.Find.ClearFormatting()
        if ($search.Find.Execute('README, R08F02, revision-final; faltan fecha de entrega, horas por autor, reparto de tareas, capturas finales y cierre')) {
            $search.Text = 'README, R08F02 y revisión final: capturas incorporadas; pendientes fecha de entrega, horas reales, reparto validado y cierre académico.'
        }
        foreach ($section in $doc.Sections) {
            if ($section.Footers.Item(1).PageNumbers.Count -eq 0) { $section.Footers.Item(1).PageNumbers.Add(2, $true) | Out-Null }
        }
        $doc.Fields.Update() | Out-Null
        $doc.Repaginate()
        foreach ($toc in $doc.TablesOfContents) {
            $toc.Update() | Out-Null
            $toc.Range.Font.Size = 10
            $toc.Range.ParagraphFormat.SpaceAfter = 2
            $toc.Range.ParagraphFormat.SpaceBefore = 0
        }
        $doc.Repaginate()
        foreach ($toc in $doc.TablesOfContents) { $toc.UpdatePageNumbers() | Out-Null }
        $doc.Save()
        $pageCount = $doc.ComputeStatistics(2)
        for ($page = 1; $page -le $pageCount; $page++) {
            $bits = $doc.ActiveWindow.Panes.Item(1).Pages.Item($page).EnhMetaFileBits
            $stream = New-Object System.IO.MemoryStream(,$bits)
            $source = [System.Drawing.Image]::FromStream($stream)
            $bitmap = New-Object System.Drawing.Bitmap 1240,1754
            $graphics = [System.Drawing.Graphics]::FromImage($bitmap)
            try {
                $graphics.Clear([System.Drawing.Color]::White)
                $graphics.DrawImage($source, 0, 0, 1240, 1754)
                $bitmap.Save((Join-Path $reviewPath ('page-{0:D2}.png' -f $page)), [System.Drawing.Imaging.ImageFormat]::Png)
            } finally {
                $graphics.Dispose()
                $bitmap.Dispose()
                $source.Dispose()
                $stream.Dispose()
            }
        }
        Write-Output "WORD_PAGES=$pageCount TOC=$($doc.TablesOfContents.Count) IMAGES=$($doc.InlineShapes.Count)"
        Write-Output "REVIEW=$reviewPath"
    } finally {
        try {
            if ($null -ne $doc -and $ownDocument) { $doc.Close($false) }
            $word.DisplayAlerts = $previousAlerts
            if ($ownWord) { $word.Quit() }
        } catch { Write-Warning "No se pudo cerrar la sesión COM de Word: $($_.Exception.Message)" }
    }
}
