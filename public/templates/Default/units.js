/*此文件中较多地调用了系统的语言, 如: langs.home, 其中langs指已缓存的语言对象, 保存在public/languages/目录下的js文件中*/

function $$(id) {
    return typeof id == "string" ? document.getElementById(id) : id;
}

//添加到收藏夹
function addToFavorite() {
	if(typeof siteConfig !=="object" || !siteConfig.siteurl || !siteConfig.sitename) return; //如果未设置JS对象及变量返回

	var d = siteConfig.siteurl;
	var c = siteConfig.sitename;
	if (document.all) {
		window.external.AddFavorite(d, c);
	} else {
		if (window.sidebar) {
			window.sidebar.addPanel(c, d, "");
		} else {
			alert(langs.er_badbrowser);
		}
	}
}

//设置cookie
function setCookie(n,val,d) {
	var e = "";
	if(d) {
		var dt = new Date();
		dt.setTime(dt.getTime() + parseInt(d)*24*60*60*1000);
		e = "; expires="+dt.toGMTString();
	}
	document.cookie = n+"="+val+e+"; path=/";
}

//获取cookie
function getCookie(n) {
	var a = document.cookie.match(new RegExp("(^| )" + n + "=([^;]*)(;|$)"));
	if (a != null) return a[2];
	return '';
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
				item[i].callBack = cb||function(){ };
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
		}catch(e){ }
		this.callBack.call(this);
		this['link']['cur'] = this;
	}
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

//JQ放大与缩小字体
(function(a) {
	a.fn.fontZoom = function(b) {
		var c = {
			obj: '',
			max: 72,
			min: 8,
			step: 2
		};
		var b = a.extend(c, b);
		this.each(function() {
			var up = a(this).find(".font_up");
			var down = a(this).find(".font_down");
			if(!b.obj || !up.length || !down.length) return;

			var o = a(b.obj);
			var osize = parseInt(o.css("fontSize"));
			var cookiesize = parseInt(getCookie("hongcmsfont"));
			if(cookiesize) {
				if(cookiesize !=  osize) {
					osize = cookiesize;
					o.css("fontSize", osize + "px");
				}
			}

			var up_ok = 1, down_ok = 1;
			var check = function(){
				if(osize <= b.min){
					down_ok = 0;
					down.addClass("font_disdown");
				}else if(osize >= b.max){
					up_ok = 0;
					up.addClass("font_disup");
				}else{
					if(!up_ok) {
						up_ok = 1;
						up.removeClass("font_disup")
					}
					if(!down_ok) {
						down_ok = 1;
						down.removeClass("font_disdown")
					}
				}
			};

			var setSize = function(type){
				osize += b.step * type;
				if(osize >= b.max){
					osize = b.max;
				}else if(osize <= b.min){
					osize = b.min;
				}

				o.css("fontSize", osize + "px");
				setCookie("hongcmsfont", osize, 365);
				check();
			};

			check();
			down.click(function(e){
				if(down_ok) setSize(-1);
				e.preventDefault();
			});

			up.click(function(e){
				if(up_ok) setSize(1);
				e.preventDefault();
			});
		});
	}
})(jQuery);


//顶部列表及下拉菜单 b为参数对象, c为下拉菜单显示后的事件函数
(function(a) {
	a.fn.Jdropdown = function(b, c) {
		if (this.length) {
			"function" == typeof b && (c = b, b = {});
			var d = a.extend({
					event: "mouseover",
					current: "hover",
					delay: 0,
					cat:false //表示是否为: 产品或文章分类下接菜单
				}, b || {}),
				e = "mouseover" == d.event ? "mouseout" : "mouseleave";
			a.each(this, function() {
				var b = null,f = null,g = !1;
				a(this).bind(d.event, function() {
					if (g) clearTimeout(f);
					else {
						var e = a(this);
						b = setTimeout(function() {
							e.addClass(d.current), g = !0, c && c(e);

							if(d.cat){ //如果是分类下拉菜单, 特殊处理
								var div = e.children().eq(1);
								var top = e.position().top - (div.height() - e.height())/2;
								if(top < 5) top = 5;
								div.css("top", top + "px");
							}
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


//JQ幻灯图片广告
(function(a) {
	a.fn.slide = function(b) {
		var c = {time: 4};
		var b = a.extend(c, b);
		this.each(function() {
			var me = a(this);
			var sWidth = me.width();
			var len = me.find("ul li").length;
			var index = 0;
			var picTimer;
			var imgs = me.find("ul li img");

			var btn = "<div class='btnBg'></div><div class='btn'>";
			for(var i=0; i < len; i++) {
				btn += "<span></span>";
			}
			btn += "</div><div class='preNext pre'></div><div class='preNext next'></div>";
			me.append(btn);
			me.find(".btnBg").css("opacity",0.5);

			me.find(".btn span").css("opacity",0.4).mouseover(function() {
				index = me.find(".btn span").index(this);
				showPics(index);
			}).eq(0).trigger("mouseover");

			me.find(".preNext").css("opacity",0.12).hover(function() {
				a(this).stop(true,false).animate({"opacity":"0.5"},300);
			},function() {
				a(this).stop(true,false).animate({"opacity":"0.12"},300);
			});

			me.find(".pre").click(function() {
				index -= 1;
				if(index == -1) {index = len - 1;}
				showPics(index);
			});

			me.find(".next").click(function() {
				index += 1;
				if(index == len) {index = 0;}
				showPics(index);
			});

			me.find("ul").css("width",sWidth * (len));
			me.hover(function() {
				clearInterval(picTimer);
			},function() {
				picTimer = setInterval(function() {
					showPics(index);
					index++;
					if(index == len) {index = 0;}
				},b.time*1000);
			}).trigger("mouseleave");

			function showPics(index) {
				var nowLeft = -index*sWidth;
				if(!imgs.eq(index).attr("src")) imgs.eq(index).attr("src", imgs.eq(index).attr("pic")).removeAttr("pic"); //图片延迟加载
				me.find("ul").stop(true,false).animate({"left":nowLeft},300);
				me.find(".btn span").stop(true,false).animate({"opacity":"0.4"},300).eq(index).stop(true,false).animate({"opacity":"1"},300);
			}
		});
	}
})(jQuery);


//JS固定DIV(顶部始终显示且不动)
function js_scrolly(p){
	var d = document, dd = d.documentElement, db = d.body, w = window, o = d.getElementById(p.id), ie6 = /msie 6/i.test(navigator.userAgent), style, timer;

	if(o){
		var cssPub = ";position:"+(p.f&&!ie6?'fixed':'absolute')+";"+(p.t!=undefined?'top:'+p.t+'px;':'bottom:0;');

		if (p.r != undefined && p.l == undefined) {
			o.style.cssText += cssPub + ('right:'+p.r+'px;');
		} else {
			o.style.cssText += cssPub + ('margin-left:'+p.l+'px;');
		}

		if(p.f&&ie6){
			var cssTop = ';top:expression(documentElement.scrollTop +'+(p.t==undefined?dd.clientHeight-o.offsetHeight:p.t)+'+ "px" );';
			var cssRight = ';right:expression(documentElement.scrollright + '+(p.r==undefined?dd.clientWidth-o.offsetWidth:p.r)+' + "px")';

			if (p.r != undefined && p.l == undefined) {
				o.style.cssText += cssRight + cssTop;
			} else {
				o.style.cssText += cssTop;
			}

			dd.style.cssText +=';background-image: url(about:blank);background-attachment:fixed;';
		}else{
			if(!p.f){
				w.onresize = w.onscroll = function(){
					clearInterval(timer);

					timer = setInterval(function(){
						//双选择为了修复chrome 下xhtml解析时dd.scrollTop为 0
						var st = (dd.scrollTop||db.scrollTop),c;
						c = st - o.offsetTop + (p.t!=undefined?p.t:(w.innerHeight||dd.clientHeight)-o.offsetHeight);
						if(c!=0){
							o.style.top = o.offsetTop + Math.ceil(Math.abs(c)/10)*(c<0?-1:1) + 'px';
						}else{
							clearInterval(timer);
						}
					},10);
				};
			}
		}
	}
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


//图片延迟加载
(function(a) {
	a.fn.lazyload = function(j) {
		if (!this.length) {
			return;
		}
		var f = a.extend({
			offsetParent: null,
			source: "original",
			placeholderClass: "loading1",
			threshold: 200
		},
		j || {}),
		k = this, g, m,
		l = function(r) {
			var u = r.scrollLeft,
			t = r.scrollTop,
			s = r.offsetWidth,
			q = r.offsetHeight;
			while (r.offsetParent) {
				u += r.offsetLeft;
				t += r.offsetTop;
				r = r.offsetParent;
			}
			return {left: u, top: t, width: s, height: q};
		},
		e = function() {
			var v = document.documentElement,
			r = document.body,
			u = window.pageXOffset ? window.pageXOffset: (v.scrollLeft || r.scrollLeft),
			t = window.pageYOffset ? window.pageYOffset: (v.scrollTop || r.scrollTop),
			s = v.clientWidth,
			q = v.clientHeight;
			return {left: u, top: t, width: s, height: q};
		},
		d = function(w, v) {
			var y, x, s, r, q, u,
			z = f.threshold ? parseInt(f.threshold) : 0;
			y = w.left + w.width / 2;
			x = v.left + v.width / 2;
			s = w.top + w.height / 2;
			r = v.top + v.height / 2;
			q = (w.width + v.width) / 2;
			u = (w.height + v.height) / 2;
			return Math.abs(y - x) < (q + z) && Math.abs(s - r) < (u + z);
		},
		b = function(q, s, r) {
			if (f.placeholderClass) r.parent().parent().addClass(f.placeholderClass);
			if (q) r.attr("src", s).removeAttr(f.source);
 		},
		o = function() {
			m = e(),
			k = k.filter(function() {return a(this).attr(f.source);});
			a.each(k,
			function() {
				var t = a(this).attr(f.source);
				if (!t)  return;
 				var s = (!f.offsetParent) ? m: l(a(f.offsetParent).get(0)), r = l(this), q = d(s, r);
				b(q, t, a(this));
			});
		},
		h = function() {
			if (k.length > 0) {
				clearTimeout(g);
				g = setTimeout(function() {o();}, 10);
			}
		};
		o();
		if (!f.offsetParent) a(window).bind("scroll", function() {h();}).bind("reset", function() {h();});
		else a(f.offsetParent).bind("scroll", function() {h();});
	};
})(jQuery);


//点击返回顶部按钮JS特效
var scrolltotop = {
	setting: {
		startline: 100, //滚动条向下移动多少px后出现
		scrollto: 0,
		scrollduration: 50, //点击后上向滚动开始到完成所需要的时间
		fadeduration: [500, 100]
	},
	controlHTML: '<a href="javascript:void(0);" class="scrollTopImg" hidefocus="true"></a>',
	controlattrs: {
		offsetx: 140,
		offsety: 0 //按钮离底部的距离
	},
	anchorkeyword: "#top",
	state: {
		isvisible: false,
		shouldvisible: false
	},
	scrollup: function() {
		if (!this.cssfixedsupport) {
			this.$control.hide()
		}
		var a = isNaN(this.setting.scrollto) ? this.setting.scrollto: parseInt(this.setting.scrollto);
		if (typeof a == "string" && jQuery("#" + a).length == 1) {
			a = jQuery("#" + a).offset().top
		} else {
			a = 0
		}
		this.$body.animate({
			scrollTop: a
		},
		this.setting.scrollduration)
	},
	keepfixed: function() {
		var c = jQuery(window);
		var b = c.scrollLeft() + c.width() - this.$control.width() - this.controlattrs.offsetx;
		var a = c.scrollTop() + c.height() - this.$control.height() - 0 - this.controlattrs.offsety;
		if (a > ($(document).height() - 20)) {
			a = $(document).height() - 20
		}
		this.$control.css({
			left: "50%",
			top: a + "px"
		})
	},
	togglecontrol: function() {
		var a = jQuery(window).scrollTop();
		if (!this.cssfixedsupport) {
			this.keepfixed()
		}
		this.state.shouldvisible = (a >= this.setting.startline) ? true: false;
		if (this.state.shouldvisible && !this.state.isvisible) {
			this.$control.stop().fadeIn(200);
			this.state.isvisible = true;
			$("#top").addClass("top_top");
		} else {
			if (this.state.shouldvisible == false && this.state.isvisible) {
				this.$control.stop().fadeOut(200);
				this.state.isvisible = false
				$("#top").removeClass("top_top");
			}
		}
	},
	init: function() {
			jQuery(document).ready(function(c) {
			var a = scrolltotop;
			var b = document.all;
			a.cssfixedsupport = !b || b && document.compatMode == "CSS1Compat" && window.XMLHttpRequest;
			a.$body = (window.opera) ? (document.compatMode == "CSS1Compat" ? c("html") : c("body")) : c("html,body");
			a.$control = c('<div id="topcontrol">' + a.controlHTML + "</div>").css({
				position: a.cssfixedsupport ? "fixed": "absolute",
				bottom: 1,
				left: "50%",
				marginLeft: "501px",
				display: "none",
				cursor: "pointer"
			}).attr({
				title: langs.backtotop
			}).click(function() {
				a.scrollup();
				return false
			}).appendTo("body");

			if (document.all && !window.XMLHttpRequest && a.$control.text() != "") {
				a.$control.css({
					width: a.$control.width()
				})
			}
			a.togglecontrol();
			c('a[href="' + a.anchorkeyword + '"]').click(function() {
				a.scrollup();
				return false
			});
			c(window).bind("scroll resize",
			function(g) {
				a.togglecontrol();
			})
		})
	}
};


//JQuery滚动组图片
(function(a) {
	a.fn.GimageMove = function(b) {
		var c = {
			prevId: "#prevBtn",
			nextId: "#nextBtn",
			offbtnleft: "",
			offbtnright: "",
			pageId: "", //当前页数的span样式
			totalId: "", //全部页数的span样式
			controlsFade: true, //无法滚动时按键切换样式
			vertical: false, //滚动方向
			speed: 800,
			auto: false,
			pause: 2000,
			continuous: false,
			offline: 1 //表示一次走几个UL
		};
		var b = a.extend(c, b);
		this.each(function() {
			var m = b.offline;
			var e = a(this);
			var l = a("ul", e).length;
			var j = a("ul", e).width();
			var f = a("li", e).height();
			var g = l % m;
			var big = a(b.bigId);
			var hasBig = big.length;

			if(b.pageId) a(b.pageId).html(1); //改变当前页数字为1
			if(b.totalId) a(b.totalId).html(l); //改变全部页数字

			if (g != 0) {
				g = parseInt((l / m))
			} else {
				g = parseInt((l / m)) - 1
			}
			var k = 0;
			if (!b.vertical) {
				a("ul", e).css("width", j)
			}
			a(b.nextId).click(function() {d("next", true)});
			a(b.prevId).click(function() {d("prev", true)});
			function d(h, n) {
				var o = k;
				switch (h) {
				case "next":
					k = (o >= g) ? (b.continuous ? 0: g) : k + 1;
					break;
				case "prev":
					k = (k <= 0) ? (b.continuous ? g: 0) : k - 1;
					break;
				case "first":
					k = 0;
					break;
				case "last":
					k = g;
					break;
				default:
					break
				}

				if(b.pageId) a(b.pageId).html(k+1); //改变当前页数字

				var r = Math.abs(o - k);
				var q = r * b.speed;
				if (!b.vertical) {
					p = (k * j * -1);
					e.animate({
						marginLeft: m * p
					},
					q)
				} else {
					p = (k * f * -1);
					e.animate({
						marginTop: m * p
					},
					q)
				}

				if (!b.continuous && b.controlsFade) {
					if (k == g) {
						a(b.nextId).addClass(b.offbtnright)
					} else {
						a(b.nextId).removeClass(b.offbtnright)
					}
					if (k == 0) {
						a(b.prevId).addClass(b.offbtnleft)
					} else {
						a(b.prevId).removeClass(b.offbtnleft)
					}
				}
				if (n) {
					clearTimeout(i)
				}
				if (b.auto && !n) {
					if(h == "next"){
						i = setTimeout(function() {
							d(((k >= g) ? "prev" : "next"), false)
						},	r * b.speed + b.pause);
					}else if(h == "prev"){
						i = setTimeout(function() {
							d(((k <= 0) ? "next" : "prev"), false)
						},	r * b.speed + b.pause);
					}
				}

				if (h == "next" && o < (l - 1)) {
					//点下一页时显示延迟加载的图片
					var hidesrc = "";
					var imgs = e.find("div[step='"+k+"'] img");
					imgs.each(function(){
						hidesrc = $(this).attr("hide");
						if(hidesrc){
							$(this).parent().addClass("loading1");
							$(this).attr("src", hidesrc).removeAttr("hide");
						}
					});

					if(hasBig){
						var now = e.find('div[now="1"]');
						var next = e.find("div[step='"+k+"']:first");
						bigNow(now, next);
						Check(next);
					}
				}else if(h == "prev" && o >0){
					var hidesrc = "";
					var imgs = e.find("div[step='"+k+"'] img");
					imgs.each(function(){
						hidesrc = $(this).attr("original");
						if(hidesrc){
							$(this).parent().addClass("loading1");
							$(this).attr("src", hidesrc).removeAttr("hide");
						}
					});

					if(hasBig){
						hasNext = true;
						var now = e.find('div[now="1"]');
						var divs = e.find("div[step='"+k+"']");
						var next = divs.eq(divs.length - 1);
						bigNow(now, next);
						Check(next);
					}
				}
			}

			var i;
			if (b.auto) {
				i = setTimeout(function() {
					d("next", false)
				},
				b.pause);
			}
			if (!b.continuous && b.controlsFade) a(b.prevId).addClass(b.offbtnleft);
			if (k == g) a(b.nextId).addClass(b.offbtnright);

			//下面是大图片点击时产生上一张下一张效果
			if(!hasBig) return;
			var hasPrev = false;
			var hasNext = true;
			var PrevBtn = a(b.prevId);
			var NextBtn = a(b.nextId);

			var Check = function(obj){
				if(!obj.parent().next().children().length && NextBtn.hasClass(b.offbtnright)){
					hasNext = false;
					hasPrev = true;
					big.removeClass(b.right);
				}else if(!obj.parent().prev().children().length && PrevBtn.hasClass(b.offbtnleft)){
					hasPrev = false;
					hasNext = true;
					big.removeClass(b.left);
				}else{
					hasPrev = true;
					hasNext = true;
				}
			};

			var bigNow = function(now, next) {
				var img = next.find("img:first");
				var src = img.attr("src");
				if(!src) src = img.attr("original");
				big.removeAttr("src").addClass("loading2").attr("src", src.replace(/_s/ig, "_l"));
				now.removeClass("now").removeAttr("now");
				next.attr("now", "1").addClass("now");
			};

			var toNext = function(){
				var now = e.find('div[now="1"]');
				var next = now.parent().next().children();
				if(next.length){
					next = next.eq(0);
					bigNow(now, next);
					Check(next);
				}else if(!NextBtn.hasClass(b.offbtnright)){
					NextBtn.click();
				}
			};

			var toPrev = function(){
				var now = e.find('div[now="1"]');
				var prev = now.parent().prev().children();
				if(prev.length){
					prev = prev.eq(0);
					bigNow(now, prev);
					Check(prev);
				}else if(!PrevBtn.hasClass(b.offbtnleft)){
					PrevBtn.click();
				}
			};

			big.mousemove(function(e){
				var X = big.position().left + big.width()/2;
				big.unbind("click");
				if(e.pageX <= X){
					big.removeClass(b.right);
					if(hasPrev){
						big.addClass(b.left);
						big.click(toPrev);
					}else{
						big.addClass(b.disable);
						big.click(function(){
							showInfo(langs.firstone, '', '', 0.8);
						});
					}
				}else{
					big.removeClass(b.left);
					if(hasNext){
						big.addClass(b.right);
						big.click(toNext);
					}else{
						big.addClass(b.disable);
						big.click(function(){
							showInfo(langs.lastone, '', '', 0.8);
						});
					}
				}
			});

			//点击组图片
			e.find("div.x").hover(function(){
				$(this).addClass("hover");
			},function(){
				$(this).removeClass("hover");
			}).click(function(){
				if(!$(this).attr("now")){
					var now = e.find('div[now="1"]');
					bigNow(now, $(this));
					window.location.hash  = '#g';
					Check($(this));
				}
			});
		})
	}
})(jQuery);


//Ajax封装
//参数loading可以是jq对象或jq选择器
//参数hide表示加载完成后loading是否隐藏
//参数dialog表示是否对返回的错误信息显示对话框
//参数lclass表示loading的CSS样式
var ajax_isOk = 1;
function ajax(url, send_data, callback, loading, hide, dialog, lclass) {
	var lclass = lclass ? lclass : "loading1";
	if(!ajax_isOk) return false;
	$.ajax({
		url: url,
		data: send_data,
		type: "post",
		cache: false,
		dataType: "json",
		beforeSend: function(){
			 ajax_isOk = 0; //防止同一操作多次点击, 重复提交
			if(!loading) return;
			if(typeof loading === "object"){
				loading.addClass(lclass).show();
			}else{
				$(loading).addClass(lclass).show();
			}
		},
		complete: function(){
			ajax_isOk = 1;
			if(!loading) return;
			if(typeof loading === "object"){
				loading.removeClass(lclass);
				if(hide) loading.hide();
			}else{
				$(loading).removeClass(lclass);
				if(hide) $(loading).hide();
			}
		},
		success: function(data){
			if(dialog && data.s == 0){
				showInfo(data.i, langs.ajaxerror);
			}else{
				if(callback) callback(data);
			}
		},
		error: function(XHR, Status, Error) {
			var info =  langs.data + ": " + XHR.responseText + "<br>" + langs.status + ": " + Status + "<br>" + langs.error + ": " + Error + "<br>";
			showInfo(info, langs.ajaxerror);
		}
	});
}

//更新验证码
function ChangeCaptcha(i){
	var a = Math.random();
	var url = i.src;
	i.src= url.split('&')[0] + '&' + a;
}

//注册及登录框表单验证
function validate_input(value, name){
	if(!value) return false;
	value = $.trim(value); //去掉空格, 并检查
	if(!value) return false;

	switch(name){
		case "username": var pattern = /^[\w\u0391-\uFFE5]{2,20}$/; break;
		case "email": var pattern = /^\w+([-+.]\w+)*@\w+([-.]\w+)+$/i; break;
		case "repassword":
		case "password": var pattern = /^[\w\u0391-\uFFE5]{4,20}$/; break;
		case "vvc": var pattern = /^[\w\u0391-\uFFE5]{3,8}$/; break;
	}

	if(name && pattern){
		return pattern.test(value);
	}else{
		return true;  //没有正则比较时, 返回成功
	}
}

//显示提示信息 callback表示对话框关闭时执行的函数; success表示是成功信息还是错误信息; time是自动关闭时间(秒)
function showInfo(info, title, callback, time, success){
	var ti = time? time * 1000 : 0;

	if(success){
		var title = "<font color=#33CC00>" + (title? title : langs.systeminfo) + "</font>";
		var content = "<font color=blue>" + info + "</font>";
	}else{
		var title = "<font color=red>" + (title? title : langs.systeminfo) + "</font>";
		var content = "<font color=#FF9900>" + info + "</font>";
	}

	easyDialog.open({
		container:{
			header: title,
			content: content,
			yesFn:function(){},
			yesText: langs.d_yes
		},
		autoClose:ti,
		callback: callback
	});

	$("#easyDialogYesBtn").focus(); //确定按钮获得焦点
}

//显示确认操作对话框 callback表示按确定时执行的函数; time是自动关闭时间;
function showDialog(info, title, callback, time){
	var ti = time? time * 1000 : 0;

	easyDialog.open({
		container:{
			header: "<font color=red>" + (title? title : langs.systeminfo) + "</font>",
			content: "<font color=#FF9900>" + info + "</font>",
			yesFn: callback,
			yesText: langs.d_yes,
			noFn:true,
			noText: langs.d_no
		},
		autoClose:ti
	});

	$("#easyDialogYesBtn").focus(); //确定按钮获得焦点
}


//用户登录及找回密码对话框, 以下4年参数必须有
//参数loginurl: 登录url; getpassurl: 找回密码url; cookiename: 安全cookie名称; cookievalue: 安全cookie的值;
function openLogin(loginurl, getpassurl, cookiename, cookievalue){
	if(!loginurl || !getpassurl || !cookiename || !cookievalue) return false;

	ajax_for = 1;  //设置一个全局的变量: 1表示登录 0表示找加密码
	var loading = "#u_login form b.i"; //先确定ajax指示器的JQ选择器
	var container = $("#login_container");
	var html = container.html();
	container.html("");

	//仅允许当前回车提交
	$(document).keydown(function(e){
		if(e.which == 13 && $("#login_container").length) $('#easyDialogYesBtn').click();
	});

	easyDialog.open({
		container:{
			header: langs.welcome_signin,
			content: html,
			yesFn:function(){
				setCookie(cookiename, cookievalue);   //提交时才设置安全cookie

				$("#u_login form b.i").removeClass("wrong"); //提交前去掉状态样式
				$("#login_info").hide();

				var info_obj = $("#login_info");

				//判断登录还是找回密码
				if(ajax_for == 1){
					var nameinput = $("#u_login_form input[name=username]");
					var passinput = $("#u_login_form input[name=password]");

					//先做简单的JS验证
					if(!validate_input(nameinput.val(), "username")){
						nameinput.focus().next().addClass("wrong");
						info_obj.html(langs.enter_un + " (2 - 20" + langs.characters +")").show();
						return false;
					}else if(!validate_input(passinput.val(), "password")){
						passinput.focus().next().addClass("wrong");
						info_obj.html(langs.enter_ps + " (4 - 20" + langs.characters +")").show();
						return false;
					}

					var form_data =  $("#u_login_form").serialize(); //取得登录form数据

					ajax(loginurl, form_data, function(data){
						if(data.s == 0){
							info_obj.html(data.i).show();

							if(data.d == 1){
								//用户名错误
								nameinput.focus().next().addClass("wrong");
							}else if(data.d == 2){
								//密码错误
								passinput.focus().next().addClass("wrong");
							}

						}else if(data.s == 1){
							//登录验证成功
							$("#user_links #u_name").html(data.d.nickname);
							$("#user_links .u_pms").html(data.d.pms);
							if(data.d.pms > 0) $("#user_links .u_pms").addClass("new").attr("title", data.d.pms + ' ' + langs.u_pms);

							$("#user_links").show();
							$("#guest_links").hide();

							user_rights = eval('('+data.d.rights+')'); //更新全局变量user_rights用户权限对象, 以便当前页面发短信, 评论等验证
							showInfo(langs.u_logined, '', '', 1, true); //显示提示信息, 1秒后自动关闭
							container.remove(); //销毁container
						}

					}, loading);
				}else if(ajax_for == 0){
					var emailinput = $("#u_forgot_form input[name=email]");

					//先做简单的JS验证
					if(!validate_input(emailinput.val(), "email")){
						emailinput.focus().next().addClass("wrong");
						info_obj.html(langs.enter_em + "!").show();
						return false;
					}

					var form_data =  $("#u_forgot_form").serialize(); //取得找回密码form数据

					ajax(getpassurl, form_data, function(data){
						if(data.s == 0){
							info_obj.html(data.i).show();
							emailinput.focus().next().addClass("wrong");
						}else if(data.s == 1){
							container.html(html); //将登录框的html恢复, 否则登录框无法再次打开
							showInfo(langs.i_getpassmail, '', '', 0, true); //显示提示信息, 不自动关闭
						}
					}, loading);
				}

				return false; //不关闭对话框
			},
			yesText: langs.signin,
			noFn:function(){
				container.html(html);
			},
			noText: langs.d_no
		}
	});

	$("#u_login_form input[name=username]").focus(); //首次打开对话框, 用户名获焦点

	$("#forget_pass").click(function (e) {
		ajax_for = 0; //表示ajax提交时为找回密码
		$("#login_info").hide();
		$("#u_login_form").hide();
		$("#u_forgot_form").show(200);

		$("#easyDialogName").html(langs.getbackpass); //改变easyDialog的标题文字
		$("#easyDialogYesBtn").html(langs.getbackpass); //改变easyDialog的按钮文字

		e.preventDefault();
	});

	$("#back_login").click(function (e) {
		ajax_for = 1; //表示ajax提交时为登录
		$("#login_info").hide();
		$("#u_forgot_form").hide();
		$("#u_login_form").show(200);

		$("#easyDialogName").html(langs.welcome_signin); //改变easyDialog的标题文字
		$("#easyDialogYesBtn").html(langs.signin); //改变easyDialog的按钮文字

		e.preventDefault();
	});
}

var shakeobj = function(obj){shake(obj, "shake", 3);obj.val($.trim(obj.val())).focus();return false;};

// 打开发送短信对话框
// pmurl: ajax发短信url; toid: 收信人userid, toname: 收信人昵称
// key: 安全验证的key; cookiename: 安全cookie名称; cookievalue: 安全cookie的值;
function openPM(pmurl, toid, toname, key, cookiename, cookievalue){
	if(!pmurl || !toid || !toname || !key || !cookiename || !cookievalue) return;

	//如果未登录, 显示登录框
	if(!user_rights.userid || user_rights.userid == 0){$("#u_signin").click();return;}

	var html = '<div class="xbox">'+
					'<form class="u_newpm_form" onsubmit="return false;">'+
						'<input type="hidden" name="key" value="' + key + '">'+
						'<input type="hidden" name="toid" value="' + toid + '">'+
						'<div class="err"></div>'+
						'<div>'+
							'<b>' + langs.subject + ':</b><input name="subject" placeholder="Subject" type="text" autocomplete="off"> <i>*</i>'+
						'</div>'+
						'<div>'+
							'<b>' + langs.u_message + ':</b><textarea name="message" placeholder="Message"></textarea>'+
						'</div>'+
					'</form>'+
					'</div>';

	easyDialog.open({
		container:{
			header: langs.u_sendpm + '&nbsp; to: &nbsp;<i class=orangeb>' + toname + '</i>',
			content: html,
			yesFn:function(){
				setCookie(cookiename, cookievalue);   //提交时才设置安全cookie
				var form = $("form.u_newpm_form");
				var msg = form.find("textarea[name=message]");
				var subject = form.find("input[name=subject]");

				if(!$.trim(subject.val())) return shakeobj(subject);

				ajax(pmurl, form.serialize(), function(data){
					if(data.s == 1){
						showInfo(langs.u_pmsent, '', '', 2, true);
					}else{
						if(data.d) shakeobj(subject); //当data.d有值时, 判断为标题错误
						else form.find("div.err").html(data.i).show(); //其它则显示错误信息
					}
				}, msg, false, false, "loading2");

				return false; //不关闭对话框
			},
			yesText: langs.send,
			noFn:true,
			noText: langs.d_no
		},
		width:420
	});

	$("form.u_newpm_form input[name=subject]").focus(); //首次打开对话框, 标题获焦点
}


// 打开评论对话框
// url: ajax发表评论的url;  for_id: 当前产品或文章的id号;  type: 0文章 1产品
function openCOMM(url, for_id, type, key, cookiename, cookievalue, code){
	if(!url || !for_id || !key || !cookiename || !cookievalue) return;

	//如果游客没有评论权限, 显示登录框
	if(!user_rights.userid && !user_rights.comment){$("#u_signin").click();return;}

	if(!user_rights.userid){ //如果是游客创建图片验证码key值
		var vvckey = '';

		$.ajaxSetup({async: false}); //设置ajax为同步!!!
		ajax(siteConfig.siteurl + "index.php/ajax/vvckey", "", function(data){
			vvckey = parseInt(data.i);
		});

		if(!vvckey){
			showInfo(langs.er_vvc, '', '', 2);
			return;
		}else{
			$.ajaxSetup({async: true});
		}
	}

	var html = '<div class="xbox">'+
					'<form class="u_comm_form" onsubmit="return false;">'+
						'<input type="hidden" name="key" value="' + key + '">'+
						'<input type="hidden" name="for_id" value="' + for_id + '">'+
						'<input type="hidden" name="type" value="' + type + '">'+
						'<input type="hidden" name="code" value="' + code + '">'+
						'<div class="err"></div>'+
						'<div>'+
							'<b>' + langs.nickname + ':</b><input name="nickname" placeholder="Nickname" type="text" autocomplete="off" value="' + user_rights.nickname + '" class="s"><i>*</i>'+
						'</div>'+
						'<div>'+
							'<b>' + langs.comments + ':</b><textarea name="content" placeholder="Comments"></textarea><i class="i2">*</i>'+
						'</div>';
	if(vvckey){
		html +=    '<div>'+
							'<b>&nbsp;</b><img src="' + siteConfig.siteurl + 'index.php/vvc?key=' + vvckey + '" onclick="ChangeCaptcha(this);" style="cursor:pointer;vertical-align:middle;" title="' + langs.newcaptcha + '" width="200" height="40">'+
						'</div>'+
						'<div>'+
							'<b>' + langs.captcha + ':</b><input type="hidden" name="vvckey" value="' + vvckey + '"><input name="vvc" placeholder="Captcha" type="text" autocomplete="off" value="" class="s"><i class="i2">*</i>'+
						'</div>';
	}
	html +=  '</form></div>';

	easyDialog.open({
		container:{
			header: langs.submitc,
			content: html,
			yesFn:function(){
				setCookie(cookiename, cookievalue);   //提交时才设置安全cookie
				var form = $("form.u_comm_form");
				var nobj = form.find("input[name=nickname]");
				var cobj = form.find("textarea[name=content]");
				var nickname = $.trim(nobj.val());
				var content = $.trim(cobj.val());

				if(!nickname){ //js先验证一下
					if(user_rights.userid){
						nobj.val(user_rights.nickname);
					}else{
						return shakeobj(nobj);
					}
				}
				if(!content) return shakeobj(cobj);

				if(vvckey){
					var vvcobj = form.find("input[name=vvc]");
					if(!validate_input(vvcobj.val(), 'vvc')) return shakeobj(vvcobj);
				}

				ajax(url, form.serialize(), function(data){
					if(data.s == 1){
						content = content.replace(/\r\n|\r|\n/g, "<br>");
						var info, cls='', alarm = '';

						if(data.d == 1){
							info = langs.i_commok;
						}else{
							info = langs.i_cwaiting;
							alarm = "<font class=redb>" + info + "</font><br>";
							cls = " c3";
						}

						showInfo(info, '', function(){
							$("#commentstb").prepend('<tr class=c2 id="comm168"><td class="td1"><img src="' + data.avatar + '" class="user_avatar" title="' + nickname +  '"></td><td class="td2"><div class="s">' + nickname +  '<span>' + data.i + '</span></div><div class="comm' + cls + '">' + alarm + content + '</div></td></tr>');

							window.location.hash  = '#c';
							shake($("#comm168"), "shake3", 6);
						}, 2, true);

					}else{
						if(data.d == 1) shakeobj(nickname);
						else if(data.d == 2) shakeobj(content);
						else if(data.d == 3)  shakeobj(vvcobj);

						if(data.i) form.find("div.err").html(data.i).show();
					}
				}, cobj, false, false, "loading2");

				return false; //不关闭对话框
			},
			yesText: langs.submit,
			noFn:true,
			noText: langs.d_no
		},
		width:420
	});

	if(user_rights.userid) {
		$("form.u_comm_form textarea[name=content]").focus();
	}else{
		$("form.u_comm_form input[name=nickname]").focus();
	}
}


//询价对话框  url: ajax提交询价的url;  pro_id: 当前产品id号;
function openQuiry(url, pro_id, key, cookiename, cookievalue, code){
	if(!url || !pro_id || !key || !cookiename || !cookievalue) return;

	//如果游客没有询价权限, 显示登录框
	if(!user_rights.userid && !user_rights.enquiry){$("#u_signin").click();return;}

	if(!user_rights.userid){ //如果是游客创建图片验证码key值
		var vvckey = '';

		$.ajaxSetup({async: false}); //设置ajax为同步!!!
		ajax(siteConfig.siteurl + "index.php/ajax/vvckey", "", function(data){
			vvckey = parseInt(data.i);
		});

		if(!vvckey){
			showInfo(langs.er_vvc, '', '', 2);
			return;
		}else{
			$.ajaxSetup({async: true});
		}
	}

	var html = '<div class="xbox">'+
					'<form class="u_quiry_form" onsubmit="return false;">'+
						'<input type="hidden" name="pro_id" value="' + pro_id + '">'+
						'<input type="hidden" name="key" value="' + key + '">'+
						'<input type="hidden" name="code" value="' + code + '">'+
						'<div class="err"></div>'+
						'<div>'+
							'<b>' + langs.nickname + ':</b><input name="username" placeholder="Nickname" type="text" autocomplete="off" value="' + user_rights.nickname + '" class="s"><i>*</i>'+
						'</div>'+
						'<div>'+
							'<b>' + langs.subject + ':</b><input name="title" placeholder="Subject" type="text" autocomplete="off"><i>*</i>'+
						'</div>'+
						'<div>'+
							'<b>' + langs.email + ':</b><input name="email" placeholder="Email" type="text" autocomplete="off" value="' + user_rights.email + '"><i>*</i>'+
						'</div>'+
						'<div>'+
							'<b>' + langs.enquiry + ':</b><textarea name="content" placeholder="Enquiry"></textarea><i class="i2">*</i>'+
						'</div>';
	if(vvckey){
		html +=    '<div>'+
							'<b>&nbsp;</b><img src="' + siteConfig.siteurl + 'index.php/vvc?key=' + vvckey + '" onclick="ChangeCaptcha(this);" style="cursor:pointer;vertical-align:middle;" title="' + langs.newcaptcha + '" width="200" height="40">'+
						'</div>'+
						'<div>'+
							'<b>' + langs.captcha + ':</b><input type="hidden" name="vvckey" value="' + vvckey + '"><input name="vvc" placeholder="Captcha" type="text" autocomplete="off" value="" class="s"><i class="i2">*</i>'+
						'</div>';
	}
	html +=  '</form></div>';

	easyDialog.open({
		container:{
			header: langs.wantenquiry,
			content: html,
			yesFn:function(){
				setCookie(cookiename, cookievalue); //提交时才设置安全cookie
				var form = $("form.u_quiry_form");
				var obj_n = form.find("input[name=username]");
				var obj_t = form.find("input[name=title]");
				var obj_e = form.find("input[name=email]");
				var obj_c = form.find("textarea[name=content]");

				if(!validate_input(obj_n.val())){
					if(user_rights.userid){
						obj_n.val(user_rights.nickname);
					}else{
						return shakeobj(obj_n);
					}
				}

				if(!validate_input(obj_t.val())) return shakeobj(obj_t);
				if(!validate_input(obj_e.val(), 'email')) return shakeobj(obj_e);
				if(!validate_input(obj_c.val())) return shakeobj(obj_c);

				if(vvckey){
					var obj_v = form.find("input[name=vvc]");
					if(!validate_input(obj_v.val(), 'vvc')) return shakeobj(obj_v);
				}

				ajax(url, form.serialize(), function(data){
					if(data.s == 1){
						showInfo(langs.i_quiryok, '', '', 4, true);
					}else{
						if(data.d == 1) shakeobj(obj_t);
						else if(data.d == 2) shakeobj(obj_c);
						else if(data.d == 3) shakeobj(obj_e);
						else if(data.d == 4) shakeobj(obj_v);
						else if(data.d == 5) shakeobj(obj_n);
							
						if(data.i) form.find("div.err").html(data.i).show();
					}
				}, obj_c, false, false, "loading2");

				return false; //不关闭对话框
			},
			yesText: langs.submit,
			noFn:true,
			noText: langs.d_no
		},
		width:420
	});

	$("form.u_quiry_form input[name=username]").focus();
}


//页面加载完成后运行
$(function(){
	//图片延迟加载
	$("img[original]").lazyload();

	//顶部菜单自动选中
	var href, topmenulinks, linkfoundtop = false;
	topmenulinks = $("#menu > dl > dt > a");
	topmenulinks.each(function(){
		href = $(this).attr('href');
		if(this_uri == href || this_uri == (href + '/') || href == (this_uri + '/')){
			$(this).parent().addClass("on");
			linkfoundtop = true;
			return false;
		}
	});
	if(!linkfoundtop){
		topmenulinks.each(function(i){
			if(i !=0 && this_uri.indexOf($(this).attr('href')) >= 0){
				$(this).parent().addClass("on");
				linkfoundtop = true;
				return false;
			}
		});
		if(!linkfoundtop) topmenulinks.eq(0).parent().addClass("on"); //如果未找到, 将首页菜单改为选中
	}

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
				$(this).attr("src", siteConfig.t_url + "images/noavatar1.gif");
			}else{
				$(this).attr("src", siteConfig.t_url + "images/noavatar.gif");
			}
		}
	});

	//切换语言动作
	$("#top .cn").click(function(){
		setCookie('hongcmslang168', 'Chinese', 30);
		document.location=window.location.href.replace(/#[\w]*/ig, '');
	});
	$("#top .en").click(function(){
		setCookie('hongcmslang168', 'English', 30);
		document.location=window.location.href.replace(/#[\w]*/ig, '');
	});

	//顶部下拉列表和顶部菜单
	$("#top .menu, #menu .menu").Jdropdown({delay:50});
	//产品分类下拉菜单
	$("#menu .sub").Jdropdown({delay:200, cat: true});

	//搜索关键词变化
	$(".web_s input.key").focus(function(){
		$(this).val("").parent().addClass("web_shover");
	}).blur(function(){
		if(!$.trim($(this).val())) $(this).parent().removeClass("web_shover");
	});

	//搜索按钮
	$(".web_s form").submit(function(e) {
		var key = $.trim($(this).children().eq(0).val());
		if(!key || key.length < 2) {
			showInfo(langs.search_err, '', function(){$(this).children().eq(0).focus();}, 3);
			e.preventDefault();
		}
	});

	//固定顶部Div不随页面滚动
	js_scrolly({id:'shortcut', l:0, t:0, f:1});

	//加载scrolltop
	scrolltotop.init();

	//用户登录
	$("#u_signin").click(function(e) {
		if(!$("#login_container").html()) return false; //如果不存在或已经打开了, 返回
		login(); //调用footer.html中的login函数
		e.preventDefault();
	});

	//小图显示中号缩略图特效
	$("div.i_thumb_1 img").mousemove(function(e) {
		ShowBigImage(e, $(this));
	});

	//新短信闪烁
	if($(".u_pms").hasClass("new")) {setInterval(function(){$(".u_pms").toggleClass("new")}, 600);}

	//用户退出登录
	$(".u_logout").click(function(e) {
		if(typeof siteConfig !== "object" || !siteConfig.siteurl) return false; //如果没有siteurl, 不操作
		showDialog(langs.logoutinfo, '', function(){
			ajax(siteConfig.siteurl + "index.php/ajax/logout", "", function(data){

				$("#user_links").hide();
				$("#guest_links").show();
				user_rights = {}; //更新全局user_rights用户权限为空对象, 当前页面下任何权限丢失

				//显示提示信息, 2秒后自动关闭. 如果当前页面在用户中心内, 对话框关闭后跳转到首页
				showInfo(langs.u_logouted, '', (this_uri.indexOf('/uc') >= 0 ? function(){document.location = siteConfig.siteurl;} : ''), 2, true);
			});
		});

		e.preventDefault();
	});

});