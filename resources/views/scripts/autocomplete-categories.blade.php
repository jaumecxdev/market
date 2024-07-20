<script>
    $(function() {

        $("#category_name").autocomplete({

            minLength: 2,
            select: function (event, ui) {//trigger when you click on the autocomplete item
                event.preventDefault();//you can prevent the default event
                //alert(JSON.stringify(ui.item, null, 4));//employee id
                $("#category_name").val(ui.item.value);
                $("#category_id").val(ui.item.id); // display the selected text
            },

            source: function(request, response) {
                $.ajax({
                    url: "{{ route('autocomplete.categories') }}",
                    data: {
                        term : request.term
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
