$(function() {
    $('.form-integration-aws').each(function() {
        var $form    = $(this);
        var $headers = $form.find('.form-control-item-headers');

        function setupForm() {
            var headers = [];
            if ($headers.val()) {
                headers = $headers.val().trim().split("\n").map(function(item) {
                    var parts = item.split(':');
                    return {
                        name:  parts[0],
                        value: parts[1]
                    }
                });
            }

            var $formWidget = $('<div class="form-widget" />');
            $('<label />')
                .text('Meta Data')
                .appendTo($formWidget);

            for (var i = 0; i < headers.length; i++) {
                var $wrap       = $('<div class="d-flex pb-2 form-group-aws-meta" />');
                var $inputName  = $('<input type="text" name="_name" class="form-control" style="margin-right: 5px; width:240px;" />')
                    .val(headers[i].name);
                var $inputValue = $('<input type="text" name="_value" class="form-control" style="margin-right: 5px; width:240px;" />')
                    .val(headers[i].value);
                var $btnRemove  = $('<button type="button" class="btn-alt" style="width:80px;">Remove</button>')
                    .on('click', handleRemove.bind(null, i));
                $wrap.append($inputName);
                $wrap.append($inputValue);
                $wrap.append($btnRemove);
                $formWidget.append($wrap);
            }

            var $newWrap       = $('<div class="d-flex pb-2" />');
            var $newInputName  = $('<input type="text" name="_name" class="form-control" placeholder="Name:" style="margin-right: 5px; width:240px;" />');
            var $newInputValue = $('<input type="text" name="_value" class="form-control" placeholder="Value:" style="margin-right: 5px; width:240px;" />');
            var $newBtnRemove  = $('<button type="button" class="btn-main" style="width:80px;">Add</button>')
                .on('click', handleAdd);
            $newWrap.append($newInputName);
            $newWrap.append($newInputValue);
            $newWrap.append($newBtnRemove);

            $formWidget.append($newWrap);
            $('<div class="form-help">Headers added to each file transferred to S3.</div>').appendTo($formWidget);

            $headers.parents('.form-widget').before($formWidget);

            function handleAdd() {
                var name  = $newInputName.val();
                var value = $newInputValue.val();
                if (name && value) {
                    $headers.val($.trim($headers.val()) + "\n" + name + ':' + value);

                    $headers.parents('.form-widget').prev('.form-widget').remove();
                    setupForm();
                }
            }

            function handleRemove(index) {
                var newHeaders = [];
                for (var i = 0; i < headers.length; i++) {
                    if (i === index) continue;
                    newHeaders.push(headers[i].name + ':' + headers[i].value);
                }
                $headers.val(newHeaders.join("\n"));
                $headers.parents('.form-widget').prev('.form-widget').remove();
                setupForm();
            }
        }

        setupForm();

        $form.on('submit', function(e) {
            var newHeaders = [];
            $form.find('.form-group-aws-meta').each(function() {
                var $meta = $(this);
                var name  = $meta.find('input[name="_name"]').val();
                var value = $meta.find('input[name="_value"]').val();
                newHeaders.push(name + ':' + value);

            });
            $headers.val(newHeaders.join("\n"));
        });
    });
});
