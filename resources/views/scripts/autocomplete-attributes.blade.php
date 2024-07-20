<script>
    $(function() {

        $("#attribute_name").autocomplete({

            minLength: 2,
            select: function (event, ui) {//trigger when you click on the autocomplete item
                event.preventDefault();//you can prevent the default event
                //alert(JSON.stringify(ui.item, null, 4));//employee id
                $("#attribute_name").val(ui.item.label);
                $("#attribute_id").val(ui.item.id);
            },

            source: function(request, response) {
                $.ajax({
                    url: "{{ route('autocomplete.attributes') }}",
                    data: {
                        term : request.term,
                        categories_id: "{{ json_encode($property->market_attribute->market_category->categories()->pluck('categories.id')->toArray()) }}",
                    },
                    dataType: "json",
                    success: function(data){
                        //console.log(JSON.stringify(data, null, 4));
                        var resp = $.map(data,function(obj){

                            return {id: obj.id, value: obj.name, label: '('+obj.category_name+') '+obj.name};

                        });

                        response(resp);
                    }
                });
            }

        });

    });
</script>
