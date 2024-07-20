<script>
    $(function() {

        $(".delete").on("submit", function () {
            return (confirm("{{ $question }}"))
        });

    });
</script>
