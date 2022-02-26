$(document).ready(function () {
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

  $('.btn-outlined').on('click', function (e) {
    e.preventDefault();

    var item = $('input[name=item_tags]').val();
    $('.first-tr').after(
      '<tr><td><input type="hidden" value="' +
        item +
        '" name="item_list[]"></input>' +
        item +
        '</td></tr>'
    );
    var i = 0;
    $('.search-input').val('');
    $('.form-btn').prop('disabled', false);
    $('.btn-outlined').prop('disabled', true);
  });
});
var i = 0;
function buttonClick() {
  i++;
  document.getElementById('item-count').value = i;
}
