$(document).ready(function () {
  //check if input is empty
  $('.add-form').on('change keyup', '.search-input', function (e) {
    let Disabled = true;
    $('.search-input').each(function () {
      let value = this.value;
      if (value && value.trim() != '') {
        Disabled = false;
      } else {
        Disabled = true;
        return false;
      }
    });

    if (Disabled) {
      $('.btn-outlined').prop('disabled', true);
    } else {
      $('.btn-outlined').prop('disabled', false);
    }
  });

  //enter item tags
  $('.btn-outlined').on('click', function (e) {
    e.preventDefault();
    var itemTagsValue = $('#itemTags').val();
    var $tds = $('#validation-table tr > td').filter(function () {
      return $.trim($(this).text()) == itemTagsValue;
    });
    if ($tds.length != 0) {
      Swal.fire('', itemTagsValue + ' already exists.', 'error');
    } else {
      checkInput();
    }

    $('.search-input').val('');
    $('.btn-outlined').prop('disabled', true);
  });
});

//Sending PR via email
$('#send').on('click', function (e) {
  var fileCount = $('#fileCount').val();
  if (fileCount != 0) {
    $('#sendingFilesText').html(
      '<i class="text-success">Sending<span class="dot_one">.</span><span class="dot_two">.</span><span class="dot_three">.</span></i>'
    );
    var options_add = {
      url: 'marlon_emailPdf.php',
      success: function () {
        $('#sendingFilesText').html('<i class="text-success">Done!</i>');
        setTimeout(function () {
          location.reload();
        }, 3000);
      },
    };
    $('#emailForm').ajaxForm(options_add);
  } else {
    $('#sendingFilesText').html('<i class="text-danger">No Files</i>');
    e.preventDefault();
    setTimeout(function () {
      location.reload();
    }, 1000 * 60 * 60);
  }
});

//count table rows
var i = 0;
function countRow() {
  i++;
  $('#item-count').val(i);
}

//check item tags
function checkInput() {
  var item_tags = $('#itemTags').val();
  $.ajax({
    url: 'marlon_validation.php?itemTag=' + item_tags,
    method: 'POST',
    success: function (response) {
      var respData = JSON.parse(response);
      if (respData.poContentId == 'none') {
        Swal.fire(respData.resp, '', 'error');
      } else {
        $('.first-tr').after(
          '<tr><td><input type="hidden" value="' +
            item_tags +
            '" name="item_list_input[]"></input><input type="hidden" value="' +
            respData.poContentId +
            '" name="poContent_list_input[]"></input>' +
            item_tags +
            '</td></tr>'
        );
        $('.form-btn').prop('disabled', false);

        countRow();
      }
    },
  });
}
