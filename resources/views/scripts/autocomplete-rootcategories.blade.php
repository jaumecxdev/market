<script>
    $(function() {

        $("#root_category_name").autocomplete({

            minLength: 2,
            select: function (event, ui) {
                event.preventDefault();
                //alert(JSON.stringify(ui.item, null, 4));//employee id
                $("#root_category_name").val(ui.item.value);
                $("#root_category_id").val(ui.item.id);
            },

            source: function(request, response) {
                $.ajax({
                    url: "{{ route('autocomplete.rootcategories') }}",
                    data: {
                        term : request.term,
                        market_id: "{{ $market_id ?? null }}"
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
