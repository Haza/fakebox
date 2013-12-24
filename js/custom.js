$(document).ready(function() {

  $(".headers").hide();
  $("button.header").click(function(){
    $(".headers").toggle('fast');
  });

  $('.date').hide();
  $('.date').addClass('absolute');

  $("ul li").on({
    mouseenter: function () {
      console.log($(this));
      $('.date', this).show();
      $(document).bind('mousemove', function(e){
        $('.date', this).offset({left: e.pageX + 20, top: e.pageY + 20});
      });
    },
    mouseleave: function () {
      $('.date', this).hide();
    }
  });


});
