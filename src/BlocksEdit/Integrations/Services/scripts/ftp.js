$(function() {
    $('.form-integration-ftp').each(function() {
        var $form = $(this);
        var $type = $form.find('.form-control-item-type');
        var $cert = $form.find('.form-control-item-cert');
        var $port = $form.find('.form-control-item-port');
        var $pasv = $form.find('.form-control-item-pasv').parents('.form-widget');

        function handleTypeChange() {
            var val     = $type.val();
            var $widget = $cert.parents('.form-widget');

            if (val === '') {
                $widget.slideUp();
            } else if (val === 'ftp') {
                $widget.slideUp();
                $port.val(21);
                $pasv.slideDown();
            } else {
                $widget.slideDown();
                $port.val(22);
                $pasv.slideUp();
            }
        }

        $type.on('change', handleTypeChange);
        handleTypeChange();

        if ($type.val() === 'sftp' && $cert.data('is-set')) {
            var $certWidget = $cert.parents('.form-widget');
            var $newWidget  = $('<div class="form-widget" />');
            var $label  = $('<div>Certificate added.</div>');
            $newWidget.append($label);
            var $button = $('<button type="button" class="btn-main">Choose new certificate.</button>');
            $newWidget.append($button);

            $button.on('click', () => {
                $newWidget.slideUp();
                $certWidget.slideDown();
            });

            $certWidget.after($newWidget);
            $certWidget.hide();
        }
    });
});
