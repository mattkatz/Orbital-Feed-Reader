<div id="opml-dialog" class="modal-window invisible">
    <div class="clickable dismiss" onclick="opml_dismiss()">
      X
    </div>
  <div class="horizontal-form">
    <!--<form id="upload_form" enctype="multipart/form-data" method="post" onsubmit='uploadOpml()'>-->
    <label>
      Select an OPML file to import
      <input type="file" name="import-opml" value="" id="import-opml" placeholder="Select an OPML file" onchange="fileSelected()"/>
    </label>
    <div id="fileName">
      
    </div>
    <div id="fileSize">
      
    </div>
    <button type='submit' id="uploadButton"  disabled=true  onclick='uploadOpml()'>
      Upload
    </button>
    <!--</form>-->
  </div>
</div>

<script type="text/javascript" language="javascript" charset="utf-8">
function getFile(){
  var file = document.getElementById('import-opml').files[0];
  return file;
}
function opml_dismiss(){
  console.log('OK!');
  jQuery('#opml-dialog').toggleClass('invisible');
  jQuery('#import-opml').attr('value','');
}

function fileSelected(){
  //var file = jQuery('#import_opml').files[0];
  var file = getFile();
  var fileSize = 0;
  if(file.size > 1024 * 1024){
    fileSize = (Math.round(file.size * 100 / (1024 * 1024)) / 100).toString() + 'MB';
  }
  else{
    fileSize = (Math.round(file.size * 100 / 1024) / 100).toString() + 'KB';
  }
  jQuery('#fileName').html('Name: '+ file.name);
  jQuery('#fileSize').html('Size: '+ fileSize);
  jQuery('#uploadButton').removeProp('disabled');
}


function uploadOpml(){
  // Check for the various File API support.
  if (window.File && window.FileReader && window.FileList && window.Blob) {
  // Great success! All the File APIs are supported.
    var f = getFile();
    var reader = new FileReader();
    //reader.onprogress = updateProgress;
    reader.onload = (function (theFile){
      return function (e){
        //parse the opml and upload it
        //console.log(e.target.result);
        try{
          var opml =  jQuery.parseXML(e.target.result);
          jQuery(opml).find('outline[xmlUrl]').each(function(index){
            var el = jQuery(this);
            var feed = {};
            feed.feed_id = null;
            //TODO later we should let people choose before we upload.
            feed.is_private = false;
            feed.feed_name = el.attr('text'); 
            feed.feed_url = el.attr('xmlUrl');
            feed.site_url = el.attr('htmlUrl');
            Wprss.feedsController.saveFeed(feed);
          });
        }
        catch(ex){
          alert('Sorry, we had trouble reading this file through.');
          console.log(ex);
        }
        opml_dismiss();

      };
    })(f);
    reader.readAsText(f);

    console.log('great success!');
    return false;
    
  } else {
  alert('Unfortunately, this browser is a bit busted.  File reading will not work, and I have not written a different way to upload opml.  Try using the latest firefox or chrome');
  }
}
</script>

