<?php
// 1. CARGAMOS CONFIGURACIÓN Y CABECERA
include 'includes/config.php'; 
include 'includes/header.php'; 

// Función para ordenar opciones en modo CUESTIONARIO
function cmp($a, $b) { return strcmp($a[0], $b[0]); }

// 2. LÓGICA DE OBTENCIÓN DE DATOS
$cat = isset($_GET["categoria"]) ? htmlspecialchars($_GET["categoria"]) : 'General';
$examen = str_contains($cat, 'CUESTIONARIO');

// Query principal
if ($examen) {
    $sql = "select id, pregunta, respuesta, img_path, justif from ptype where categoria = ? ORDER BY id";
} else {
    $sql = "select id, pregunta, respuesta, img_path, justif from ptype where categoria = ? ORDER BY RAND()";
}

$stmt = mysqli_prepare($link, $sql);
mysqli_stmt_bind_param($stmt, "s", $cat);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $id, $pregunta, $respuesta, $img_path, $justif);

$preguntas = [];
while (mysqli_stmt_fetch($stmt)) {
    array_push($preguntas, array($id, $pregunta, $respuesta, $img_path, $justif));
}
mysqli_stmt_close($stmt);
?>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="text-primary fw-bold"><i class="fa-solid fa-clipboard-question"></i> <?php echo $cat; ?></h2>
        <span class="badge bg-secondary fs-6 rounded-pill"><?php echo count($preguntas); ?> Preguntas</span>
    </div>

    <div id="resultado-final" class="alert alert-info d-none mb-4 shadow-sm border-0 text-center">
        <h3 class="fw-bold"><i class="fa-solid fa-square-poll-vertical"></i> Resultado Final</h3>
        <p class="fs-4 mb-0">Has acertado <span id="aciertos" class="text-success fw-bold">0</span> de <span id="total-preg" class="fw-bold"><?php echo count($preguntas); ?></span></p>
        <p class="fs-5 text-muted">Fallos: <span id="fallos" class="text-danger fw-bold">0</span></p>
        <button class="btn btn-outline-primary mt-3 btn-sm" onclick="window.location.reload();">Reiniciar Test</button>
    </div>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <?php
            $qIndex = 1;
            foreach ($preguntas as $p) {
                // ... (mismo código de obtención de opciones que ya tenías)
                $pId = $p[0]; $pTexto = $p[1]; $pCorrecta = $p[2]; $pImg = $p[3]; $pJustif = $p[4];
                $opciones = [];
                array_push($opciones, array($pCorrecta, $pJustif, true));

                $sql2 = ($examen) ? "select respuesta, justif from incorrectas where id_pregunta = ? ORDER BY respuesta" : "select respuesta, justif from incorrectas where id_pregunta = ? ORDER BY RAND() limit 3";
                $stmt2 = mysqli_prepare($link, $sql2);
                mysqli_stmt_bind_param($stmt2, "s", $pId);
                mysqli_stmt_execute($stmt2);
                mysqli_stmt_bind_result($stmt2, $opcion_inc, $argum_inc);
                while (mysqli_stmt_fetch($stmt2)) { array_push($opciones, array($opcion_inc, $argum_inc, false)); }
                mysqli_stmt_close($stmt2);

                if (!$examen) { shuffle($opciones); } else { usort($opciones, "cmp"); }
            ?>
            
            <div class="card mb-5 shadow-sm border-0 pregunta-card" data-pregunta-id="<?php echo $pId; ?>">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h5 class="fw-bold text-dark d-flex">
                        <span class="badge bg-primary me-3 align-self-start"><?php echo $qIndex++; ?></span> 
                        <span><?php echo htmlspecialchars($pTexto); ?></span>
                    </h5>
                </div>
                <div class="card-body p-4">
                    <?php if (!empty($pImg)): ?>
                        <div class="text-center mb-4">
                            <img src="assets/img/<?php echo $pImg; ?>" class="img-fluid rounded border shadow-sm" style="max-height: 300px;">
                        </div>
                    <?php endif; ?>

                    <div class="list-group">
                        <?php foreach ($opciones as $opt): 
                            $textoOpt = $opt[0]; $justifOpt = $opt[1]; $esCorrecta = $opt[2] ? 'true' : 'false';
                        ?>
                        <button type="button" class="list-group-item list-group-item-action opcion-test p-3 mb-2 rounded border" 
                                onclick="verificarRespuesta(this, <?php echo $esCorrecta; ?>)">
                            <div class="d-flex w-100 justify-content-between align-items-center">
                                <div class="d-flex align-items-start flex-grow-1 me-2">
                                    <i class="fa-regular fa-circle mt-1 me-3 text-secondary icon-state"></i>
                                    <span class="mb-0 fs-6"><?php echo htmlspecialchars($textoOpt); ?></span>
                                </div>
                            </div>
                            <?php if (!empty($justifOpt) || ($opt[2] && !empty($pJustif))): ?>
                                <div class="alert alert-warning mt-2 mb-0 py-2 small d-none justificacion text-start">
                                    <i class="fa-solid fa-lightbulb me-1 text-warning"></i> 
                                    <?php echo htmlspecialchars(($opt[2] && !empty($pJustif)) ? $pJustif : $justifOpt); ?>
                                </div>
                            <?php endif; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php } ?>

            <div class="text-center mt-5 mb-5">
                <button type="button" class="btn btn-primary btn-lg px-5 shadow" onclick="finalizarTest()">
                    <i class="fa-solid fa-flag-checkered me-2"></i> Finalizar y ver resultados
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .opcion-test { transition: all 0.2s; border: 1px solid #dee2e6 !important; }
    .list-group-item-success { background-color: #d1e7dd !important; border-color: #badbcc !important; color: #0f5132; }
    .list-group-item-danger { background-color: #f8d7da !important; border-color: #f5c2c7 !important; color: #842029; }
    .fw-bold { font-weight: 700 !important; }
    .pregunta-card.no-respondida { border: 2px solid #ffc107 !important; }
</style>

<script>
// Variables globales para el conteo
let contadorAciertos = 0;
let contadorFallos = 0;

function verificarRespuesta(elemento, esCorrecta) {
    const contenedorCuerpo = elemento.closest('.list-group');
    if (contenedorCuerpo.classList.contains('answered')) return;
    contenedorCuerpo.classList.add('answered');
    
    // Contabilizar
    if (esCorrecta) {
        contadorAciertos++;
    } else {
        contadorFallos++;
    }
    
    const opciones = contenedorCuerpo.querySelectorAll('.opcion-test');
    opciones.forEach(opt => {
        opt.style.cursor = 'default';
        const esEstaLaCorrecta = opt.getAttribute('onclick').includes('true');
        if (esEstaLaCorrecta) {
            opt.classList.add('list-group-item-success', 'fw-bold');
            const icon = opt.querySelector('.icon-state');
            icon.classList.replace('fa-regular', 'fa-solid');
            icon.classList.replace('fa-circle', 'fa-circle-check');
            icon.classList.add('text-success');
            const j = opt.querySelector('.justificacion');
            if(j) j.classList.remove('d-none');
        }
    });

    if (!esCorrecta) {
        elemento.classList.add('list-group-item-danger');
        const iconError = elemento.querySelector('.icon-state');
        iconError.classList.replace('fa-regular', 'fa-solid');
        iconError.classList.replace('fa-circle', 'fa-circle-xmark');
        iconError.classList.add('text-danger');
        const jError = elemento.querySelector('.justificacion');
        if(jError) jError.classList.remove('d-none');
    }
}

function finalizarTest() {
    // Actualizar los textos del panel de resultados
    document.getElementById('aciertos').innerText = contadorAciertos;
    document.getElementById('fallos').innerText = contadorFallos;
    
    // Mostrar el panel
    const panel = document.getElementById('resultado-final');
    panel.classList.remove('d-none');
    
    // Hacer scroll suave hacia arriba para ver el resultado
    window.scrollTo({ top: 0, behavior: 'smooth' });

    // (Opcional) Resaltar preguntas que no fueron respondidas
    const cards = document.querySelectorAll('.list-group');
    cards.forEach(card => {
        if (!card.classList.contains('answered')) {
            card.closest('.pregunta-card').classList.add('no-respondida');
        }
    });
}
</script>

<?php include 'includes/footer.php'; ?>