;(function ($) {
  $.fn.countdown = function (options) {
    var defaults = {
      //各种默认参数
    }
    var options = $.extend(defaults, options); //传入的参数覆盖默认参数
    this.each(function () {
      var _this = $(this); //缓存一下插件传进来的节点对象。
      //执行内容
      var intDiff = parseInt(_this.data('timestamp'))
      window.setInterval(function() {
          var day = 0,
              hour = 0,
              minute = 0,
              second = 0; //时间默认值     
          if (intDiff > 0) {
              day = Math.floor(intDiff / (60 * 60 * 24));
              hour = Math.floor(intDiff / (60 * 60)) - (day * 24);
              minute = Math.floor(intDiff / 60) - (day * 24 * 60) - (hour * 60);
              second = Math.floor(intDiff) - (day * 24 * 60 * 60) - (hour * 60 * 60) - (minute * 60);
          }
          if (minute <= 9) minute = '0' + minute;
          if (second <= 9) second = '0' + second;
          _this.text(day + "天" + hour + '时' + minute + '分' + second + '秒')
          intDiff--;
      }, 1000);
    })
    return $(this); //把节点对象返回去，为了支持链式调用。
  }
})(jQuery);