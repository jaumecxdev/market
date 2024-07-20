<script>
    $(function() {

        $("#item_reference").autocomplete({

            minLength: 2,
            select: function (event, ui) {
                event.preventDefault();
                //alert(JSON.stringify(ui.item, null, 4));
                $("#item_reference").val(ui.item.value);
                $("#product_id").val(ui.item.id);
            },

            source: function(request, response) {
                $.ajax({
                    url: "{{ route('autocomplete.products') }}",
                    data: {
                        term : request.term,
                        supplier_id: "{{ $supplier_id ?? null }}",
                        suppliers_id: "{{ $suppliers_id ?? null }}"
                    },
                    dataType: "json",
                    success: function(data){
                        //console.log(JSON.stringify(data, null, 4));
                        response(data);
                    }
                });
            }

        }).autocomplete( "instance" )._renderItem = function( ul, item ) {
            return $("<li>")
                .append("<div>" + item.label + "</div>")
                .appendTo( ul );
        };

    });
</script>
