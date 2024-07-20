<script>
    $(function () {

        let textarea_toolbar = [
            // [groupName, [list of button]]
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
            ['fontname', ['fontname', 'fontsize', 'fontsizeunit', 'superscript', 'subscript']],
            ['height', ['height', 'hr']],
            ['color', ['color', 'forecolor', 'backcolor']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'video']],       // ['link', 'picture', 'video']
            ['view', ['fullscreen', 'codeview', 'help']],
        ];

        $('.textarea#shortdesc').summernote({
            height: 100,
            toolbar: textarea_toolbar,
        });

        $('.textarea#longdesc').summernote({
            height: 400,
            toolbar: textarea_toolbar,
        });

        $('#disable-summernote').click(function() {
            $('.textarea#shortdesc').summernote('destroy');
            $('.textarea#longdesc').summernote('destroy');
            $('#disable-summernote').hide();
        });

    });
</script>
