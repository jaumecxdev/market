<script>
    $(function() {

        $(".delete").on("click", function () {
            if (confirm("{{ $question }}") == true)
                this.submit();
        });

    });
</script>
