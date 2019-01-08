!function(e){var t={};function n(r){if(t[r])return t[r].exports;var o=t[r]={i:r,l:!1,exports:{}};return e[r].call(o.exports,o,o.exports,n),o.l=!0,o.exports}n.m=e,n.c=t,n.d=function(e,t,r){n.o(e,t)||Object.defineProperty(e,t,{configurable:!1,enumerable:!0,get:r})},n.n=function(e){var t=e&&e.__esModule?function(){return e.default}:function(){return e};return n.d(t,"a",t),t},n.o=function(e,t){return Object.prototype.hasOwnProperty.call(e,t)},n.p="",n(n.s="multi /src/scripts/user-center.js")}({"21It":function(e,t,n){"use strict";var r=n("FtD3");e.exports=function(e,t,n){var o=n.config.validateStatus;n.status&&o&&!o(n.status)?t(r("Request failed with status code "+n.status,n.config,null,n.request,n)):e(n)}},"5VQ+":function(e,t,n){"use strict";var r=n("cGG2");e.exports=function(e,t){r.forEach(e,function(n,r){r!==t&&r.toUpperCase()===t.toUpperCase()&&(e[t]=n,delete e[r])})}},"7GwW":function(e,t,n){"use strict";var r=n("cGG2"),o=n("21It"),i=n("DQCr"),s=n("oJlt"),u=n("GHBc"),c=n("FtD3"),a="undefined"!=typeof window&&window.btoa&&window.btoa.bind(window)||n("thJu");e.exports=function(e){return new Promise(function(t,f){var p=e.data,d=e.headers;r.isFormData(p)&&delete d["Content-Type"];var l=new XMLHttpRequest,h="onreadystatechange",g=!1;if("undefined"==typeof window||!window.XDomainRequest||"withCredentials"in l||u(e.url)||(l=new window.XDomainRequest,h="onload",g=!0,l.onprogress=function(){},l.ontimeout=function(){}),e.auth){var m=e.auth.username||"",y=e.auth.password||"";d.Authorization="Basic "+a(m+":"+y)}if(l.open(e.method.toUpperCase(),i(e.url,e.params,e.paramsSerializer),!0),l.timeout=e.timeout,l[h]=function(){if(l&&(4===l.readyState||g)&&(0!==l.status||l.responseURL&&0===l.responseURL.indexOf("file:"))){var n="getAllResponseHeaders"in l?s(l.getAllResponseHeaders()):null,r={data:e.responseType&&"text"!==e.responseType?l.response:l.responseText,status:1223===l.status?204:l.status,statusText:1223===l.status?"No Content":l.statusText,headers:n,config:e,request:l};o(t,f,r),l=null}},l.onerror=function(){f(c("Network Error",e,null,l)),l=null},l.ontimeout=function(){f(c("timeout of "+e.timeout+"ms exceeded",e,"ECONNABORTED",l)),l=null},r.isStandardBrowserEnv()){var v=n("p1b6"),w=(e.withCredentials||u(e.url))&&e.xsrfCookieName?v.read(e.xsrfCookieName):void 0;w&&(d[e.xsrfHeaderName]=w)}if("setRequestHeader"in l&&r.forEach(d,function(e,t){void 0===p&&"content-type"===t.toLowerCase()?delete d[t]:l.setRequestHeader(t,e)}),e.withCredentials&&(l.withCredentials=!0),e.responseType)try{l.responseType=e.responseType}catch(t){if("json"!==e.responseType)throw t}"function"==typeof e.onDownloadProgress&&l.addEventListener("progress",e.onDownloadProgress),"function"==typeof e.onUploadProgress&&l.upload&&l.upload.addEventListener("progress",e.onUploadProgress),e.cancelToken&&e.cancelToken.promise.then(function(e){l&&(l.abort(),f(e),l=null)}),void 0===p&&(p=null),l.send(p)})}},DQCr:function(e,t,n){"use strict";var r=n("cGG2");function o(e){return encodeURIComponent(e).replace(/%40/gi,"@").replace(/%3A/gi,":").replace(/%24/g,"$").replace(/%2C/gi,",").replace(/%20/g,"+").replace(/%5B/gi,"[").replace(/%5D/gi,"]")}e.exports=function(e,t,n){if(!t)return e;var i;if(n)i=n(t);else if(r.isURLSearchParams(t))i=t.toString();else{var s=[];r.forEach(t,function(e,t){null!=e&&(r.isArray(e)?t+="[]":e=[e],r.forEach(e,function(e){r.isDate(e)?e=e.toISOString():r.isObject(e)&&(e=JSON.stringify(e)),s.push(o(t)+"="+o(e))}))}),i=s.join("&")}return i&&(e+=(-1===e.indexOf("?")?"?":"&")+i),e}},FtD3:function(e,t,n){"use strict";var r=n("t8qj");e.exports=function(e,t,n,o,i){var s=new Error(e);return r(s,t,n,o,i)}},GHBc:function(e,t,n){"use strict";var r=n("cGG2");e.exports=r.isStandardBrowserEnv()?function(){var e,t=/(msie|trident)/i.test(navigator.userAgent),n=document.createElement("a");function o(e){var r=e;return t&&(n.setAttribute("href",r),r=n.href),n.setAttribute("href",r),{href:n.href,protocol:n.protocol?n.protocol.replace(/:$/,""):"",host:n.host,search:n.search?n.search.replace(/^\?/,""):"",hash:n.hash?n.hash.replace(/^#/,""):"",hostname:n.hostname,port:n.port,pathname:"/"===n.pathname.charAt(0)?n.pathname:"/"+n.pathname}}return e=o(window.location.href),function(t){var n=r.isString(t)?o(t):t;return n.protocol===e.protocol&&n.host===e.host}}():function(){return!0}},"JP+z":function(e,t,n){"use strict";e.exports=function(e,t){return function(){for(var n=new Array(arguments.length),r=0;r<n.length;r++)n[r]=arguments[r];return e.apply(t,n)}}},JuPC:function(e,t,n){"use strict";function r(e,t){for(var n=0;n<t.length;n++){var r=t[n];r.enumerable=r.enumerable||!1,r.configurable=!0,"value"in r&&(r.writable=!0),Object.defineProperty(e,r.key,r)}}n.d(t,"a",function(){return o});var o=function(){function e(){!function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,e),this.now=new Date,this.year=this.now.getFullYear(),this.month=this.now.getMonth()+1}var t,n,o;return t=e,(n=[{key:"init",value:function(){this.appendYear(),this.appendMonth(),this.appendDay(),this.onYearChange(),this.onMonthChange()}},{key:"appendYear",value:function(){for(var e="",t=this.year;t>=this.year-130;t--)e+="<option value=".concat(t,">").concat(t,"</option>");$("#year").append(e)}},{key:"appendMonth",value:function(){for(var e="",t=1;t<=12;t++)e+="<option value=".concat(t,">").concat(t,"</option>");$("#month").append(e)}},{key:"appendDay",value:function(){for(var e="",t=this.getTotalDays(this.year,this.month),n=1;n<=t;n++)e+="<option value=".concat(n,">").concat(n,"</option>");$("#day").empty().append(e),$("#day").selectpicker("refresh")}},{key:"onYearChange",value:function(){var e=this;$("#year").change(function(){e.year=$(this).val(),e.appendDay()})}},{key:"onMonthChange",value:function(){var e=this;$("#month").change(function(){e.month=$(this).val(),e.appendDay()})}},{key:"getTotalDays",value:function(e,t){return new Date(e,t,0).getDate()}}])&&r(t.prototype,n),o&&r(t,o),e}()},KCLY:function(e,t,n){"use strict";var r=n("cGG2"),o=n("5VQ+"),i={"Content-Type":"application/x-www-form-urlencoded"};function s(e,t){!r.isUndefined(e)&&r.isUndefined(e["Content-Type"])&&(e["Content-Type"]=t)}var u,c={adapter:("undefined"!=typeof XMLHttpRequest?u=n("7GwW"):"undefined"!=typeof process&&(u=n("7GwW")),u),transformRequest:[function(e,t){return o(t,"Content-Type"),r.isFormData(e)||r.isArrayBuffer(e)||r.isBuffer(e)||r.isStream(e)||r.isFile(e)||r.isBlob(e)?e:r.isArrayBufferView(e)?e.buffer:r.isURLSearchParams(e)?(s(t,"application/x-www-form-urlencoded;charset=utf-8"),e.toString()):r.isObject(e)?(s(t,"application/json;charset=utf-8"),JSON.stringify(e)):e}],transformResponse:[function(e){if("string"==typeof e)try{e=JSON.parse(e)}catch(e){}return e}],timeout:0,xsrfCookieName:"XSRF-TOKEN",xsrfHeaderName:"X-XSRF-TOKEN",maxContentLength:-1,validateStatus:function(e){return e>=200&&e<300}};c.headers={common:{Accept:"application/json, text/plain, */*"}},r.forEach(["delete","get","head"],function(e){c.headers[e]={}}),r.forEach(["post","put","patch"],function(e){c.headers[e]=r.merge(i)}),e.exports=c},Re3r:function(e,t){function n(e){return!!e.constructor&&"function"==typeof e.constructor.isBuffer&&e.constructor.isBuffer(e)}e.exports=function(e){return null!=e&&(n(e)||function(e){return"function"==typeof e.readFloatLE&&"function"==typeof e.slice&&n(e.slice(0,0))}(e)||!!e._isBuffer)}},TNV1:function(e,t,n){"use strict";var r=n("cGG2");e.exports=function(e,t,n){return r.forEach(n,function(n){e=n(e,t)}),e}},XmWM:function(e,t,n){"use strict";var r=n("KCLY"),o=n("cGG2"),i=n("fuGk"),s=n("xLtR");function u(e){this.defaults=e,this.interceptors={request:new i,response:new i}}u.prototype.request=function(e){"string"==typeof e&&(e=o.merge({url:arguments[0]},arguments[1])),(e=o.merge(r,{method:"get"},this.defaults,e)).method=e.method.toLowerCase();var t=[s,void 0],n=Promise.resolve(e);for(this.interceptors.request.forEach(function(e){t.unshift(e.fulfilled,e.rejected)}),this.interceptors.response.forEach(function(e){t.push(e.fulfilled,e.rejected)});t.length;)n=n.then(t.shift(),t.shift());return n},o.forEach(["delete","get","head","options"],function(e){u.prototype[e]=function(t,n){return this.request(o.merge(n||{},{method:e,url:t}))}}),o.forEach(["post","put","patch"],function(e){u.prototype[e]=function(t,n,r){return this.request(o.merge(r||{},{method:e,url:t,data:n}))}}),e.exports=u},cGG2:function(e,t,n){"use strict";var r=n("JP+z"),o=n("Re3r"),i=Object.prototype.toString;function s(e){return"[object Array]"===i.call(e)}function u(e){return null!==e&&"object"==typeof e}function c(e){return"[object Function]"===i.call(e)}function a(e,t){if(null!=e)if("object"!=typeof e&&(e=[e]),s(e))for(var n=0,r=e.length;n<r;n++)t.call(null,e[n],n,e);else for(var o in e)Object.prototype.hasOwnProperty.call(e,o)&&t.call(null,e[o],o,e)}e.exports={isArray:s,isArrayBuffer:function(e){return"[object ArrayBuffer]"===i.call(e)},isBuffer:o,isFormData:function(e){return"undefined"!=typeof FormData&&e instanceof FormData},isArrayBufferView:function(e){return"undefined"!=typeof ArrayBuffer&&ArrayBuffer.isView?ArrayBuffer.isView(e):e&&e.buffer&&e.buffer instanceof ArrayBuffer},isString:function(e){return"string"==typeof e},isNumber:function(e){return"number"==typeof e},isObject:u,isUndefined:function(e){return void 0===e},isDate:function(e){return"[object Date]"===i.call(e)},isFile:function(e){return"[object File]"===i.call(e)},isBlob:function(e){return"[object Blob]"===i.call(e)},isFunction:c,isStream:function(e){return u(e)&&c(e.pipe)},isURLSearchParams:function(e){return"undefined"!=typeof URLSearchParams&&e instanceof URLSearchParams},isStandardBrowserEnv:function(){return("undefined"==typeof navigator||"ReactNative"!==navigator.product)&&"undefined"!=typeof window&&"undefined"!=typeof document},forEach:a,merge:function e(){var t={};function n(n,r){"object"==typeof t[r]&&"object"==typeof n?t[r]=e(t[r],n):t[r]=n}for(var r=0,o=arguments.length;r<o;r++)a(arguments[r],n);return t},extend:function(e,t,n){return a(t,function(t,o){e[o]=n&&"function"==typeof t?r(t,n):t}),e},trim:function(e){return e.replace(/^\s*/,"").replace(/\s*$/,"")}}},cWxy:function(e,t,n){"use strict";var r=n("dVOP");function o(e){if("function"!=typeof e)throw new TypeError("executor must be a function.");var t;this.promise=new Promise(function(e){t=e});var n=this;e(function(e){n.reason||(n.reason=new r(e),t(n.reason))})}o.prototype.throwIfRequested=function(){if(this.reason)throw this.reason},o.source=function(){var e;return{token:new o(function(t){e=t}),cancel:e}},e.exports=o},dIwP:function(e,t,n){"use strict";e.exports=function(e){return/^([a-z][a-z\d\+\-\.]*:)?\/\//i.test(e)}},dVOP:function(e,t,n){"use strict";function r(e){this.message=e}r.prototype.toString=function(){return"Cancel"+(this.message?": "+this.message:"")},r.prototype.__CANCEL__=!0,e.exports=r},fuGk:function(e,t,n){"use strict";var r=n("cGG2");function o(){this.handlers=[]}o.prototype.use=function(e,t){return this.handlers.push({fulfilled:e,rejected:t}),this.handlers.length-1},o.prototype.eject=function(e){this.handlers[e]&&(this.handlers[e]=null)},o.prototype.forEach=function(e){r.forEach(this.handlers,function(t){null!==t&&e(t)})},e.exports=o},hL5n:function(e,t,n){"use strict";Object.defineProperty(t,"__esModule",{value:!0});var r=n("JuPC"),o=n("wpUP");$(function(){(new r.a).init(),$("#checkin").click(function(){o.a.usercenter.checkin().then(function(){$("#checkin span").text("已签到")})})})},mtWM:function(e,t,n){e.exports=n("tIFN")},"multi /src/scripts/user-center.js":function(e,t,n){e.exports=n("hL5n")},oJlt:function(e,t,n){"use strict";var r=n("cGG2"),o=["age","authorization","content-length","content-type","etag","expires","from","host","if-modified-since","if-unmodified-since","last-modified","location","max-forwards","proxy-authorization","referer","retry-after","user-agent"];e.exports=function(e){var t,n,i,s={};return e?(r.forEach(e.split("\n"),function(e){if(i=e.indexOf(":"),t=r.trim(e.substr(0,i)).toLowerCase(),n=r.trim(e.substr(i+1)),t){if(s[t]&&o.indexOf(t)>=0)return;s[t]="set-cookie"===t?(s[t]?s[t]:[]).concat([n]):s[t]?s[t]+", "+n:n}}),s):s}},p1b6:function(e,t,n){"use strict";var r=n("cGG2");e.exports=r.isStandardBrowserEnv()?{write:function(e,t,n,o,i,s){var u=[];u.push(e+"="+encodeURIComponent(t)),r.isNumber(n)&&u.push("expires="+new Date(n).toGMTString()),r.isString(o)&&u.push("path="+o),r.isString(i)&&u.push("domain="+i),!0===s&&u.push("secure"),document.cookie=u.join("; ")},read:function(e){var t=document.cookie.match(new RegExp("(^|;\\s*)("+e+")=([^;]*)"));return t?decodeURIComponent(t[3]):null},remove:function(e){this.write(e,"",Date.now()-864e5)}}:{write:function(){},read:function(){return null},remove:function(){}}},pBtG:function(e,t,n){"use strict";e.exports=function(e){return!(!e||!e.__CANCEL__)}},pxG4:function(e,t,n){"use strict";e.exports=function(e){return function(t){return e.apply(null,t)}}},qRfI:function(e,t,n){"use strict";e.exports=function(e,t){return t?e.replace(/\/+$/,"")+"/"+t.replace(/^\/+/,""):e}},t8qj:function(e,t,n){"use strict";e.exports=function(e,t,n,r,o){return e.config=t,n&&(e.code=n),e.request=r,e.response=o,e}},tIFN:function(e,t,n){"use strict";var r=n("cGG2"),o=n("JP+z"),i=n("XmWM"),s=n("KCLY");function u(e){var t=new i(e),n=o(i.prototype.request,t);return r.extend(n,i.prototype,t),r.extend(n,t),n}var c=u(s);c.Axios=i,c.create=function(e){return u(r.merge(s,e))},c.Cancel=n("dVOP"),c.CancelToken=n("cWxy"),c.isCancel=n("pBtG"),c.all=function(e){return Promise.all(e)},c.spread=n("pxG4"),e.exports=c,e.exports.default=c},thJu:function(e,t,n){"use strict";var r="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";function o(){this.message="String contains an invalid character"}o.prototype=new Error,o.prototype.code=5,o.prototype.name="InvalidCharacterError",e.exports=function(e){for(var t,n,i=String(e),s="",u=0,c=r;i.charAt(0|u)||(c="=",u%1);s+=c.charAt(63&t>>8-u%1*8)){if((n=i.charCodeAt(u+=.75))>255)throw new o;t=t<<8|n}return s}},wpUP:function(e,t,n){"use strict";var r=n("mtWM"),o=n.n(r);function i(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{},r=Object.keys(n);"function"==typeof Object.getOwnPropertySymbols&&(r=r.concat(Object.getOwnPropertySymbols(n).filter(function(e){return Object.getOwnPropertyDescriptor(n,e).enumerable}))),r.forEach(function(t){s(e,t,n[t])})}return e}function s(e,t,n){return t in e?Object.defineProperty(e,t,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[t]=n,e}console.log("production");var u=o.a.create({baseURL:"http://mall.admin.zotye.com/api"}),c=["/UserLogin/user_login","/UserReg/regcode","/UserReg/reg","/Article/pointDesc","/Article/pointDesc","/Article/auctionAgreement","/Article/laws","/Article/contact","/Drive/showAppoint","/Drive/show_province","/Drive/check_children","/Drive/addAppoint","/goods/carSpec"];u.interceptors.request.use(function(e){return window.localStorage.getItem("token")&&c.indexOf(e.url)<0&&(e.params||(e.params={}),e.params.token=window.localStorage.getItem("token")),e}),u.interceptors.response.use(function(e){return e.data}),t.a={order:{createOrder:function(e){return u.post("/Order/commit",{params:i({},e)})},commitOrder:function(e){return u.post("/Order/commit_order",i({},e))},payOrder:function(e,t){return u.post("/Order/pay",{master_order_sn:e,type:t})},cancelOrder:function(e,t){return u.post("/Order/delorder",{master_order_sn:t,order_id:e})},integralPay:function(e){return u.post("/Order/pointPay",{master_order_sn:e})},deleteOrder:function(e){return u.post("/Order/deleteOrder",{order_id:e})}},usercenter:{login:function(e,t){return u.post("/UserLogin/user_login",{mobile:e,password:t})},sendCode:function(e){return u.post("/UserReg/regcode",{mobile:e})},register:function(e,t,n){return u.post("/UserReg/reg",{mobile:e,code:t,password:n})},getOrders:function(e){arguments.length>1&&void 0!==arguments[1]&&arguments[1];return u.post("/Order/userOrder",{},{params:{page:1,order_type:e}})},getAfterOrders:function(){return u.post("/AfterSale/index")},checkin:function(){return u.post("/users/sign_in")},getUserInfo:function(){return u.post("/users/userinfo")},logout:function(){return u.post("/UserLogin/logout")},getCollection:function(){var e=arguments.length>0&&void 0!==arguments[0]?arguments[0]:1;return u.post("/users/collection",{},{params:{page:e}})},toggleFav:function(e,t){return u.get("/goods/collect",{params:{exchange_integral:e,goods_id:t}})},querySignLog:function(e){return u.post("/users/sign_query")},addAddress:function(e){return u.post("/UserAddress/add_address",i({},e))},getAddressList:function(){return u.get("/UserAddress/index")}},article:{getPointDesc:function(){return u.post("/Article/pointDesc")},getAuctionAgreement:function(){return u.post("/Article/auctionAgreement")},getLaws:function(){return u.post("/Article/laws")},contactUs:function(){return u.post("/Article/contact")}},drive:{getDrivelist:function(){return u.post("/Drive/showAppoint")},getProvince:function(){return u.post("/Drive/show_province")},getCity:function(e){return u.post("/Drive/check_children",{id:e})},querySales:function(e,t,n){return u.post("/Drive/sales",{cate_id2:e,province:t,city:n})},queryDealers:function(e,t,n){return u.post("/Drive/getDealers",{cate_id2:e,province:t,city:n})},addAppoint:function(e){return u.post("/Drive/addAppoint",i({},e))},getCarSpec:function(e){return u.get("/goods/carSpec",{params:e})}}}},xLtR:function(e,t,n){"use strict";var r=n("cGG2"),o=n("TNV1"),i=n("pBtG"),s=n("KCLY"),u=n("dIwP"),c=n("qRfI");function a(e){e.cancelToken&&e.cancelToken.throwIfRequested()}e.exports=function(e){return a(e),e.baseURL&&!u(e.url)&&(e.url=c(e.baseURL,e.url)),e.headers=e.headers||{},e.data=o(e.data,e.headers,e.transformRequest),e.headers=r.merge(e.headers.common||{},e.headers[e.method]||{},e.headers||{}),r.forEach(["delete","get","head","post","put","patch","common"],function(t){delete e.headers[t]}),(e.adapter||s.adapter)(e).then(function(t){return a(e),t.data=o(t.data,t.headers,e.transformResponse),t},function(t){return i(t)||(a(e),t&&t.response&&(t.response.data=o(t.response.data,t.response.headers,e.transformResponse))),Promise.reject(t)})}}});
//# sourceMappingURL=user-center-2fd9f8caf4.bundle.js.map
