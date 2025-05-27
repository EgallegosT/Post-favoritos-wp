jQuery(document).ready(function ($) {
    $(document).on('click', '.add-to-favorites', function () {
        var button = $(this);
        var provider_id = button.data('id');
        var nonce = button.data('nonce');

        if (!provider_id || !nonce) {
            alert('Error al obtener los datos. Inténtalo nuevamente.');
            console.error('Faltan datos en el botón:', { provider_id, nonce });
            return;
        }

        button.prop('disabled', true); // Deshabilitar el botón mientras se procesa

        $.post(favoritosAjax.ajaxurl, {
            action: 'favopadi_toggle_favorite',
            provider_id: provider_id,
            _wpnonce: nonce
        }).done(function (response) {
            console.log('Respuesta del servidor:', response);

            if (response.success && response.data) {
                button.text(response.data === 'Agregado' ? 'Quitar de Favoritos' : 'Agregar a Favoritos');

                // Solicitar un nuevo nonce después de la acción exitosa
                $.get(favoritosAjax.ajaxurl, { action: 'get_new_nonce' })
                    .done(function (nonceResponse) {
                        if (nonceResponse.success) {
                            button.data('nonce', nonceResponse.data);
                            console.log('Nuevo nonce actualizado:', nonceResponse.data);
                        } else {
                            console.error('Error al obtener nuevo nonce:', nonceResponse);
                        }
                    })
                    .fail(function () {
                        console.error('Error en la solicitud del nuevo nonce.');
                    });
            } else {
                alert(response.data || 'Error desconocido.');
                console.error('Error en la respuesta AJAX:', response);
            }
        }).fail(function (jqXHR, textStatus, errorThrown) {
            alert('Hubo un error al procesar la solicitud.');
            console.error('Error en AJAX:', textStatus, errorThrown);
        }).always(function () {
            button.prop('disabled', false); // Rehabilitar el botón después de la respuesta
        });
    });
});