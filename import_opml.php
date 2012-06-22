<div id="opml-dialog" class="modal-window invisible">
  <div class="horizontal-form">
    <!--<form id="upload_form" enctype="multipart/form-data" method="post" onsubmit='uploadOpml()'>-->
      <label>
        Select an OPML file to import
        <input type="file" name="import_opml" value="" id="import_opml" placeholder="Select an OPML file" onchange="fileSelected()"/>
      </label>
<div id="fileName">
  
</div>
<div id="fileSize">
  
</div>
      <button type='submit' id="uploadButton"  disabled=true  onclick='uploadOpml()'>
        Upload
      </button>
    <!--</form>-->

      <script type="text/x-handlebars">
        {{view Wprss.AddFeedView 
          name="addFeedView" 
          placeholder="Drag or copy paste a feed here" 
          valueBinding="Wprss.feedFinder.url" }}
    </script>
    
  </div>
</div>
<script type="text/javascript" language="javascript" charset="utf-8">
function getFile(){
  var file = document.getElementById('import_opml').files[0];
  return file;


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
    reader.onload = (function (theFile){
      return function (e){
        //parse the opml and upload it
        console.log(e.target.result);
      };
    })(f);
    reader.readAsText(f);

    console.log('great success!');
    return false;
    
  } else {
  alert('The File APIs are not fully supported in this browser.');
  }
}
</script>

