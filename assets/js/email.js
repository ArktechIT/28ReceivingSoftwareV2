$(document).ready(function () {
  var d = new Date();
  var month = d.getMonth() + 1;
  var date = d.getDate();
  var time = d.getHours() + ':' + d.getMinutes();

  let currentDate = month + '/' + date;

  if (
    localStorage.getItem('date') === null ||
    localStorage.date != currentDate
  ) {
    if (time >= '20:00') {
      generateReceipt();
    } else {
      $('#send-h4').removeClass('mt-5');
      $('#send-h4').addClass('mt-4');
      $('#sendingFilesText').html(
        '<i class="text-danger"><small>Sending of Proof of Receipt is unavailable.</small></i>'
      );
    }
  } else {
    $('#sendingFilesText').html(
      '<i class="text-danger"><small>Try again tomorrow.</small></i>'
    );
  }

  function generateReceipt() {
    $('#sendingFilesText').html(
      '<i class="text-success">Please wait<span class="dot_one">.</span><span class="dot_two">.</span><span class="dot_three">.</span></i>'
    );

    setTimeout(function () {
      $.ajax({
        url: 'marlon_emailPdf.php?action=generatePR',
        success: function (response) {
          if (response == 1) {
            $('#sendingFilesText').html('<i class="text-success">Done!</i>');
            localStorage.setItem('date', currentDate);
          } else {
            $('#sendingFilesText').html('<i class="text-danger">Empty!</i>');
          }
        },
      });
    }, 2500);
  }
});
