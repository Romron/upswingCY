
function ajaxTest(input)
  {
    var request = new XMLHttpRequest();
    request.open('GET', 'ajax.php?input=' + input);

    request.send();
    request.onreadystatechange = function()
      {
        if (request.readyState == 4 && request.status == 200)
          {
            processResult(request.responseText);
          }

      }
  }

function processResult(output)
  {
    var div = document.getElementById('divResult');
    div.innerHTML = output;
  }

// точка входа

window.onload = function()
  {
    ajaxTest("******************************333333333333333333333*********************************");
    document.getElementById('btnRun').onclick = function()
      {
        var input = document.getElementById('inpText');
        //ajaxTest(input.value);
        ajaxTest("ddddddd");

      }
  } 


