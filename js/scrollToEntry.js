function scrollToEntry(currentItem, bottom){
  var currentScroll = jQuery('#orbital-content').scrollTop();
  scrollAmount = -1 * currentScroll;
  if(currentItem){
    var row = jQuery('#'+currentItem.feed_id + "_" +currentItem.id);
    if(null === row.offset()){
      console.log('row.offset() was null');
      return;
    }
    //position is the offset from the parent scrollable element
    var scrollAmount = row.position().top;
  }
  if(bottom){
    console.log('trying to get to the bottom');
    scrollAmount += row.height();
  }
  jQuery('#orbital-content').animate({ scrollTop: scrollAmount + currentScroll }, 200); 
}
//Set everything up after page load
jQuery(document).ready(function($){
  jQuery('#tagentry').suggest(opts.ajaxurl + '?action=orbital_get_tags',{ delay: 500, minchars: 2, multiple: true, multipleSep: ', ' });
  function setContentHeight(id,height){
    $(id).css({'height':(($(window).height())-height)+'px'});
  }
  $(window).resize(function(){
    setContentHeight('#orbital-content',$('#wpadminbar').height()+$('#commandbar').height());
    setContentHeight('#orbital-feedlist',$('#wpadminbar').height());
    var w =$('#orbital-container').width();

    $('#orbital-content').css({'width':((w * .8)- 10 +'px')});
    //scrollbar.width probably is 10 px.
    //$('#feeds').css({'height':(($('#orbital-feedlist').height()-($('#feed-head').height()+ 10 )) +'px')});
  });
  $(window).resize();
});
