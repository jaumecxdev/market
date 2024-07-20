<script>
    $(function() {

        $("#supplier_brand_name").autocomplete({

            minLength: 2,
            select: function (event, ui) {
                event.preventDefault();
                //alert(JSON.stringify(ui.item, null, 4));//employee id
                $("#supplier_brand_name").val(ui.item.label);
                $("#supplier_brand_id").val(ui.item.id);
            },

            source: function(request, response) {
                $.ajax({
                    url: "{{ route('autocomplete.supplierbrands') }}",
                    data: {
                        term : request.term,
                        suppliers_id: "{{ $suppliers_id }}"
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
