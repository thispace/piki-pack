!function($){var t={configure:function(e,r){return this.each(function(){var s={};s.$=$(this),s.self=this,s.type=s.$.attr("type"),s.ID=s.$.attr("rel"),s.stars=s.$.children(".star"),s.scoreMedia=s.$.attr("score-media"),s.userScore=s.$.attr("user-score"),s.$.data("fiveStars",s),void 0!==r&&t[r].apply(s.$,[s,e])})},setScores:function(t,e){return this.each(function(){t.stars.removeClass("active");for(var r=1;e>=r;r++)t.stars.eq(r-1).addClass("active")})},mouseover:function(t,e){return this.each(function(){t.$.fiveStars("setScores",e.score)})},click:function(e,r){return this.each(function(){$.fn.pikiLoader(),$.ajax({url:Piki.ajaxurl,type:"POST",dataType:"JSON",data:{action:"five_stars_vote",ID:e.ID,type:e.type,score:r.score}}).done(function(r){e.userScore=r.user_score,e.scoreMedia=r.total,t.setScores.apply(e.$,[e,e.userScore]),$.fn.pikiLoader("close")}).fail(function(t,e){$.fn.pikiLoader("close"),$.fn.pikiAlert("Request failed: "+e)})})},mouseout:function(e,r){return this.each(function(){var r=e.userScore>0?e.userScore:e.scoreMedia;t.setScores.apply(e.$,[e,r])})}};$.fn.fiveStars=function(e,r){var s=$(this).data("fiveStars");return void 0===s?t.configure.apply($(this),[r,e]):t[e].apply($(this),[s,r])},$(function(){$("body").on("mouseover click mouseout",".five-stars-box .star",function(t){void 0===this.$&&(this.$=$(this),this.target=this.$.parent(),this.score=this.$.attr("score")),this.target.fiveStars(t.type,this)})})}(jQuery);