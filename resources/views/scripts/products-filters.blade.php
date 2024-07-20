<script>
    $(function () {

        $('#filters-button').click(function() {
            $( "#filters-display" ).toggle();
        });

        $('.attributes-button').click(function() {
            an = $(this).attr('attribute_name');
            $( ".attribute-display-"+an ).toggle();
            if ($(this).html() == '<i class="fas fa-plus-circle mr-2"></i>Más opciones') $(this).html('<i class="fas fa-minus-circle mr-2"></i>Menos opciones');
            else $(this).html('<i class="fas fa-plus-circle mr-2"></i>Más opciones');
        });

    });
</script>
