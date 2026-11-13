    <dialog class="carrito-panel" id="carrito-panel" aria-labelledby="carrito-panel-titulo" aria-describedby="carrito-panel-descripcion" data-carrito-panel data-carrito-url="<?= $url('carrito') ?>">
        <div class="carrito-panel__interior">
            <header class="carrito-panel__encabezado">
                <div>
                    <span class="carrito-panel__etiqueta">HECHO PARA COMPARTIR</span>
                    <h2 class="carrito-panel__titulo" id="carrito-panel-titulo">Tu carrito</h2>
                </div>
                <button class="carrito-panel__cerrar" type="button" aria-label="Cerrar carrito" data-carrito-cerrar autofocus>×</button>
            </header>
            <p class="carrito-panel__descripcion" id="carrito-panel-descripcion">Revisá tus productos antes de realizar el pedido.</p>
            <p class="carrito-panel__estado" role="status" data-carrito-estado></p>
            <div class="carrito-panel__error" role="alert" data-carrito-error hidden>
                <p>No pudimos comprobar el carrito. Volvé a cargarlo para ver su estado actual.</p>
                <button class="carrito-panel__reintentar" type="button" data-carrito-reintentar>Volver a cargar</button>
            </div>
            <div class="carrito-panel__contenido" data-carrito-contenido></div>
        </div>
    </dialog>
