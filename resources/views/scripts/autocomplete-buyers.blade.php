<script>
    $(function() {

        $("#buyer_name").autocomplete({

            minLength: 2,
            select: function (event, ui) {
                event.preventDefault();
                //alert(JSON.stringify(ui.item, null, 4));//employee id
                $("#buyer_name").val(ui.item.label);
                $("#buyer_id").val(ui.item.id);
            },

            source: function(request, response) {
                $.ajax({
                    url: "{{ route('autocomplete.buyers') }}",
                    data: {
                        term : request.term
                    },
                    dataType: "json",
                    success: function(data){
                        //console.log(JSON.stringify(data, null, 4));
                        var resp = $.map(data,function(obj){

                            return {id: obj.id, value: obj.name, label: obj.name};
                        });

                        response(resp);
                    }
                });
            }

        });

    });
</script>
