function $$(id) {
    return typeof id == "string" ? document.getElementById(id) : id;
}

//JQ闪动特效  ele: JQ要闪动的对象; cls: 闪动的类(className); times: 闪动次数
function shake(ele, cls, times){
	var i = 0, t = false, o = ele.attr("class")+" ", c = "", times = times||2;
	if(t) return;
	t= setInterval(function(){
		i++;
		c = i%2 ? o+cls : o;
		ele.attr("class",c);
		if(i==2*times){
			clearInterval(t);
			ele.removeClass(cls);
		}
	},200);
}

//移动鼠标显示较大的缩略图
function ShowBigImage(e, me, delay) {
	if(typeof ttt != 'undefined') clearTimeout(ttt);
	var ei = $("#zoom_image_div");
	if(ei.length){ //对象存在时仅是移动
		var top = e.pageY - 222;
		var left = e.pageX - 222;
		if((top - $(document).scrollTop()) < 0)  top = e.pageY + 10;
		if((left - $(document).scrollLeft()) < 0)  left = e.pageX + 10;
		ei.css("top", top + "px").css("left", left + "px");
	}else{
		ttt = setTimeout(function() {
			var obj = $("<div id=\"zoom_image_div\" style=\"padding:6px;text-align:center;border:1px solid #B2B2B2;border-radius:4px 4px 4px 4px;position:absolute; background: #FFF; z-index:80000;\"><div style=\"width:200px;height:200px;overflow:hidden;\"><table><tr><td style=\"vertical-align:middle;height:200px;padding:0;\"><img src=\"" + me.attr("src").replace(/_s/ig, "_m") + "\" width=200 border=0></td></tr></table></div></div>");
			$("body").append(obj);
			var top = e.pageY - 222;
			var left = e.pageX - 222;
			if((top - $(document).scrollTop()) < 0)  top = e.pageY + 10;
			if((left - $(document).scrollLeft()) < 0)  left = e.pageX + 10;
			obj.css("top", top + "px").css("left", left + "px");
		}, (delay || delay == 0)? delay : 600);
	}
	me.mouseout(function(){
		if(typeof ttt != 'undefined') clearTimeout(ttt);
		$("#zoom_image_div").remove();
	});
}

//TAB
function tab(o, s, cb, ev){
	var $ = function(o){return document.getElementById(o)};
	var css = o.split((s||'_'));
	if(css.length!=4)return;
	this.event = ev || 'onclick';
	o = $(o);
	if(o){
		this.ITEM = [];
		o.id = css[0];
		var item = o.getElementsByTagName(css[1]);
		var j=1;
		for(var i=0;i<item.length;i++){
			if(item[i].className.indexOf(css[2])>=0 || item[i].className.indexOf(css[3])>=0){
				if(item[i].className == css[2])o['cur'] = item[i];
				item[i].callBack = cb||function(){};
				item[i]['css'] = css;
				item[i]['link'] = o;
				this.ITEM[j] = item[i];
				item[i]['Index'] = j++;
				item[i][this.event] = this.ACTIVE;
			}
		}
		return o;
	}
}
tab.prototype = {
	ACTIVE:function(){
		var $ = function(o){return document.getElementById(o)};
		this['link']['cur'].className = this['css'][3];
		this.className = this['css'][2];
		try{
			$(this['link']['id']+'_'+this['link']['cur']['Index']).style.display = 'none';
			$(this['link']['id']+'_'+this['Index']).style.display = 'block';
		}catch(e){}
		this.callBack.call(this);
		this['link']['cur'] = this;
	}
}


//设置container, sidebar兼容浏览器
function do_fix(open){
	if(open){
		$("#container").width(180);
		$("#sidebar").css("cssText", "");
	}else{
		$("#container").width(40);
		js_scrolly({id:"sidebar", l:0, t:40});
	}
}

//左侧JS固定DIV
function js_scrolly(p){
	var o = document.getElementById(p.id);

	if(o){
		var dd = document.documentElement, ie6 = /msie 6/i.test(navigator.userAgent);
		var cssPub = ";position:"+(!ie6?'fixed':'absolute')+";"+(p.t!=undefined?'top:'+p.t+'px;':'bottom:0;');

		if (p.r != undefined && p.l == undefined) {
			o.style.cssText += cssPub + ('right:'+p.r+'px;');
		} else {
			o.style.cssText += cssPub + ('margin-left:'+p.l+'px;');
		}

		if(ie6){
			var cssTop = ';top:expression(documentElement.scrollTop +'+(p.t==undefined?dd.clientHeight-o.offsetHeight:p.t)+'+ "px" );';
			var cssRight = ';right:expression(documentElement.scrollright + '+(p.r==undefined?dd.clientWidth-o.offsetWidth:p.r)+' + "px")';

			if (p.r != undefined && p.l == undefined) {
				o.style.cssText += cssRight + cssTop;
			} else {
				o.style.cssText += cssTop;
			}

			dd.style.cssText +=';background-image: url(about:blank);background-attachment:fixed;';
		}
	}
}


//Ajax封装
var ajax_isOk = 1;  //防止同一操作多次点击, 重复提交
function ajax(url, send_data, callback) {
	if(!ajax_isOk) return false;
	$.ajax({
		url: url,
		data: send_data,
		type: "post",
		cache: false,
		dataType: "json",
		beforeSend: function(){ajax_isOk = 0;$("#ajax-loader").show();},
		complete: function(){ajax_isOk = 1;$("#ajax-loader").hide();},
		success: function(data){
			if(data.s == 0){
				$.dialog({lock:true, title:"Ajax错误", content:"<span class=red>" + data.i + "</span>", okValue:'  确定  ', ok:true});
			}else{
				if(callback) callback(data);
			}
		},
		error: function(XHR, Status, Error) {
			$.dialog({lock:true, title:"Ajax错误", content:"数据: " + XHR.responseText + "<br>状态: " + Status + "<br>错误: " + Error, okValue:'  确定  ', ok:true});
		}
	});
}

//顶部下拉菜单 b为参数对象, c为下拉菜单显示后的事件函数
(function(a) {
	a.fn.Jdropdown = function(b, c) {
		if (this.length) {
			"function" == typeof b && (c = b, b = {});
			var d = a.extend({
					event: "mouseover",
					current: "hover",
					delay: 0
				}, b || {}),
				e = "mouseover" == d.event ? "mouseout" : "mouseleave";
			a.each(this, function() {
				var b = null,f = null,g = !1;
				a(this).bind(d.event, function() {
					if (g) clearTimeout(f);
					else {
						var e = a(this);
						b = setTimeout(function() {
							e.addClass(d.current), g = !0, c && c(e)
						}, d.delay);
					}
				}).bind(e, function() {
					if (g) {
						var c = a(this);
						f = setTimeout(function() {
							c.removeClass(d.current), g = !1
						}, 0)
					} else clearTimeout(b);
				});
			});
		}
	}
})(jQuery);

var handleSidebarMenu = function () {
	var objs = $("#sidebar .has-sub > a");
	var linkfound = false, linkfoundtop = false;

	objs.mouseover(function(){
		var sub = $(this).next();

		var pixtobottom = $(window).height() + $(document).scrollTop() - $(this).offset().top - $(this).height() - 26;

		if(pixtobottom < sub.height()){
			sub.addClass("top");
		}
	});

	objs.click(function (e) {
		if($("#container").hasClass("sidebar-closed") === false) {
			var last = jQuery('.has-sub.open', $('#sidebar'));
			last.removeClass("open");
			jQuery('.arrow', last).removeClass("open");
			jQuery('.sub', last).slideUp(200);

			var sub = jQuery(this).next();
			if (sub.is(":visible")) {
				jQuery('.arrow', jQuery(this)).removeClass("open");
				jQuery(this).parent().removeClass("open");
				sub.slideUp(200);
			} else {
				jQuery('.arrow', jQuery(this)).addClass("open");
				jQuery(this).parent().addClass("open");
				sub.slideDown(200);
			}
		}
		e.preventDefault();
	});


	//查找左侧菜单当前链接并设置样式
	var href, leftmenuli, leftmenulinks, topmenulinks;
	leftmenulinks = $("#sidebar .sub > li > a");

	leftmenulinks.each(function(){
		href = $(this).attr('href');
		if(this_uri == href || this_uri == (href + '/') || href == (this_uri + '/')){
			$(this).parent().addClass("active").parent().parent().addClass("active open").find("a span.arrow").addClass("open");
			linkfound = true;
			return false;
		}
	});

	if(!linkfound){
		leftmenuli = $("#sidebar > ul > li");
		leftmenuli.not(".has-sub").each(function(){
			href = $(this).children("a").attr('href');
			if(this_uri == href || this_uri == (href + '/') || href == (this_uri + '/')){
				$(this).addClass("active");
				linkfound = true;
				return false;
			}
		});
	}

	if(!linkfound){
		leftmenulinks.each(function(){
			if(this_uri.indexOf($(this).attr('href')) >= 0){
				$(this).parent().addClass("active").parent().parent().addClass("active open").find("a span.arrow").addClass("open");
				linkfound = true;
				return false;
			}
		});

		if(!linkfound){
			leftmenuli.not(".has-sub").each(function(){
				href = $(this).children("a").attr('href');
				if(this_uri.indexOf(href) >= 0){
					$(this).addClass("active");
					return false;
				}
			});
		}
	}

	//查找顶部菜单当前链接并设置样式
	topmenulinks = $("#topmenu > dl > dd > div > li > a");
	topmenulinks.each(function(){
		href = $(this).attr('href');
		if(this_uri == href || this_uri == (href + '/') || href == (this_uri + '/')){
			$(this).addClass("active");
			linkfoundtop = true;
			return false;
		}
	});

	if(!linkfoundtop){
		topmenulinks.each(function(){
			if(this_uri.indexOf($(this).attr('href')) >= 0){
				$(this).addClass("active");
				return false;
			}
		});
	}
}

var handleSidebarToggler = function () {
	if ($.cookie('sidebar-opened')) {
		$("#container").removeClass("sidebar-closed");
		$(".sidebar-toggler").attr("title","收拢菜单(Ctrl <)");
		do_fix(true);
	}else{
		do_fix(false);
	}

	$(".sidebar-toggler").hover(function(){
		$(this).children("i").addClass("hover");
	}, function(){
		$(this).children("i").removeClass("hover");
	});
	
	$('.sidebar-toggler').click(function () {
		if ($("#container").hasClass("sidebar-closed") === false) {
			$("#container").addClass("sidebar-closed");
			$.removeCookie('sidebar-opened', {path: '/'});
			$(this).attr("title","展开菜单(Ctrl >)");
			
			do_fix(false);
		} else {
			$("#container").removeClass("sidebar-closed");
			$.cookie('sidebar-opened', 1, {expires: 365, path: '/'});
			$(this).attr("title","收拢菜单(Ctrl <)");
			
			do_fix(true);
		}
	});
}

//左侧菜单等系统功能
$(function(){
	//调整高度
	$("#container").height($(window).height()-40); 
	$(window).resize(function() {
		$("#container").height($(window).height()-40);
	});

	handleSidebarMenu();
	handleSidebarToggler();

	$("#topbar dl").Jdropdown({delay: 50}, function(a){});

	//头像文件不存在时都显示当前模板中的默认头像
	$(".user_avatar, .user_avatar1").each(function(){
		var error = false;
		if($.browser.msie){
			if(!this.complete || (typeof this.naturalWidth == "undefined" && this.naturalWidth == 0)) error = true;
		}else{
			if(!this.complete || typeof this.naturalWidth == "undefined" || this.naturalWidth == 0) error = true;
		}
		if(error){
			if($(this).hasClass("user_avatar1")){
				$(this).attr("src", t_url + "images/noavatar1.gif");
			}else{
				$(this).attr("src", t_url + "images/noavatar.gif");
			}
		}
	});

	//全选checkbox
	$("#checkAll").click(function(e){
		$("input[name=\'" + $(this).attr("for") + "\']").attr("checked", this.checked);
	});

	$(document).keydown(function(e){
		if(e.ctrlKey && (e.which == 37 || e.which == 39)) {
			$('.sidebar-toggler').click();
		}
	});
});
