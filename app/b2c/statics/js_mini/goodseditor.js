var ShopExGoodsEditor=new Class({Implements:[Options],options:{periodical:!1,delay:500,postvar:"finderItems",varname:"items",width:500,height:400},initialize:function(a,b){this.el=$(a);this.setOptions(b);this.cat_id=$("gEditor-GCat-input").get("value");this.type_id=$("gEditor-GType-input").get("value");this.goods_id=$("gEditor-GId-input").get("value");this.initEditorBody.call(this)},catalogSelect:function(a,b){var a=a||1,c=$("gEditor-GType-input");if(a!=c.get("value")&&(confirm(LANG_goodseditor.comfirm)||
0>b))c.getElement("option[value="+a+"]").set("selected",!0),this.updateEditorBody.call(this);this.cat_id=b},initEditorBody:function(){var a=this;$("gEditor-GCat-input");var b=$("gEditor-GType-input");b.addEvent("click",function(){this.store("tempvalue",this.get("value"))});b.addEvent("change",function(){var b=this.retrieve("tempvalue");this.get("value")&&confirm(LANG_goodseditor.comfirm)?(a.updateEditorBody.call(a),a.type_id=this.get("value")):this.getElement("option[value="+b+"]").set("selected",
!0)})},updateEditorBody:function(){try{$("productNode")&&$("productNode").retrieve("specOBJ")&&$("productNode").appendChild($("productNode").retrieve("specOBJ").toHideInput($("productNode").getElement("tr")))}catch(a){}var b={method:"post",data:$("gEditor").toQueryString(),url:"index.php?app=b2c&ctl=admin_goods_editor&act=update",evalScripts:!1,onComplete:function(a){var b={"#menu-desktop":$("menu-desktop"),"#gEditor-Body":$("gEditor-Body")},a=a.replace(/<\!-{5}(.*?)-{5}([\s\S]*?)-{5}(.*?)-{5}>/g,
function(a,c,d){a=c;a=b[a]||$(a);(d=d||null)&&a&&a.empty().set("html",d).fixEmpty();return""}),d="";this.response.text.replace(/<script[^>]*>([\s\S]*?)<\/script>/gi,function(a,b){d+=b+"\n";return""});Browser.exec(d);goodsEditFrame()}};(new Request(b)).send()},mprice:function(a){for(a=$(a).getParent();"TR"!=a.tagName;a=a.getParent());var b={};a.getElements("input").each(function(a){"price[]"==a.name?b.price=a.value:"goods[product][0][price]"==a.name?b.price=a.value:a.getAttribute("level")&&(b["level["+
a.getAttribute("level")+"]"]=a.value)});window.fbox=new Dialog("index.php?app=b2c&ctl=admin_goods_editor&act=set_mprice",{title:LANG_goodseditor.editvipprice,ajaxoptions:{data:b,method:"post"},modal:!0});window.fbox.onSelect=goodsEditor.setMprice.bind({base:goodsEditor,el:a})},setMprice:function(a){var b={};a.each(function(a){b[a.name]=a.value});this.el.getElements("input").each(function(a){var e=a.getAttribute("level");e&&void 0!=b[e]&&(a.value=b[e])})},spec:{addCol:function(a,b){this.dialog=new Dialog("index.php?app=b2c&ctl=admin_products&act=set_spec&_form="+
(a?a:"goods-spec")+"&p[0]="+b,{ajaxoptions:{data:$("goods-spec").toQueryString()+($("nospec_body")?"&"+$("nospec_body").toQueryString():""),method:"post"},title:LANG_goodseditor.type})},addRow:function(){this.dialog=new Dialog("index.php?app=b2c&ctl=admin_goods_editor/spec&act=addRow",{ajaxoptions:{data:$("goods-spec"),method:"post"}})}},adj:{addGrp:function(a){this.dialog=new Dialog("index.php?app=b2c&ctl=admin_goods_editor&act=addGrp&goods_id="+this.goods_id+"&_form="+(a?a:"goods-adj"),{title:LANG_goodseditor.widget})}},
pic:{del:function(a){if(confirm(LANG_goodseditor.comfirmDelPic)){var a=$(a),b=a.getParent(".gpic-box"),c=b.getNext(".gpic-box");try{a.get("ident")&&($$("#all-pics input[name=image_default]")[0]&&($$("#all-pics input[name=image_default]")[0].value=a.get("ident")),$("all-pics").eliminate("cururl"),b.destroy())}catch(e){b.destroy()}if(c)c.getElement(".gpic").onclick()}},setDefault:function(a,b){var c=$(b).getParent(".pic-main").getElement(".pic-area"),e=$E(".gpic[image_id="+a+"]",c);if(!e.hasClass("current")){var d,
f;(d=$E(".current",c))&&d.removeClass("current");(f=$E("input[name=image_default]",c))&&f.set("value",a);e.addClass("current")}},getDefault:function(){var a=obj.getParent(".pic-main").getElement(".pic-area");return(a=$E("input[name=image_default]",a))?a.value:!1},viewSource:function(a){return new Dialog(a,{title:LANG_goodseditor.viewSource,singlon:!1,width:650,height:300})}},rateGoods:{add:function(){window.fbox=new Dialog("index.php?ctl=goods/product&act=select",{modal:!0,ajaxoptions:{data:{onfinish:"goodsEditor.rateGoods.insert(data)"},
method:"post"}})},del:function(){},insert:function(a){$$("div.rate-goods").each(function(b){a["has["+b.getAttribute("goods_id")+"]"]=1});(new Request({url:"index.php?ctl=goods/product&act=ratelist",data:a,onComplete:function(a){$("x-rate-goods").innerHTML+=a}})).send()}}}),CatalogSelect=new Class({Implements:[Events,Options],options:{updateMain:"catalog-x",url:"index.php?app=b2c&ctl=admin_goods_cat&act=get_subcat_list",childClass:".subs",params:"p[0]"},initialize:function(a,b){if(a){this.handle=$(a);
this.setOptions(b);var c=this.options;this.url=c.url;if((this.updateMain=$(c.updateMain))&&this.url)this.cacheHS={},this.load.call(this)}},load:function(){this.request("0")},attach:function(){var a=this;this.updateMain.getElements(this.options.childClass).addEvent("click",function(b){var c=this.get("id")||this.get("pid");return this.hasClass("cat-no-child")?(a.callback("",this.get("text")),document.body.fireEvent("click",b)):a.isCache(c)});this.updateMain.getElements(".cat-child").addEvent("click",
function(b){var c=this.getParent("*[type_id]");c&&(a.callback(c.id,this.get("text"),c.get("type_id")),document.body.fireEvent("click",b))})},isCache:function(a){this.cacheHS.id?this.updateMain.innerHTML=this.getCache(a):this.request(a);this.fireEvent("show").attach.call(this)},request:function(){var a=this,b=Array.flatten(arguments).link({options:Object.type,id:String.type});b.options=Object.append(b.options||{},{url:this.url,data:this.options.params+"="+b.id,method:"get",onComplete:function(c){a.updateMain.innerHTML=
c;a.setCache(b.id,c).attach();a.fireEvent("show")}});(new Request(b.options)).send();return this},callback:function(a,b,c){this.handle.getElement(".label").set("text",b);this.handle.getElement("input[type=hidden]")&&(this.handle.getElement("input[type=hidden]").value=a);this.fireEvent("callback",[a,c,b])},setCache:function(a,b){void 0==this.cacheHS[a]&&(this.cacheHS[a]=b);return this},getCache:function(a){return this.cacheHS[a]}});