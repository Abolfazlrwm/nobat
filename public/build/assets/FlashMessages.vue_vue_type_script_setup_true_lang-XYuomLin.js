import{d as u,E as y,y as k,i as f,h as o,g as m,o as t,F as h,k as x,x as b,b as l,c as d,f as _,u as a,t as g,a as C}from"./app-CNVm-O1Z.js";import{b as i}from"./createLucideIcon-Pok0TEk7.js";import{X as v}from"./x-cQnAu6zS.js";/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const z=i("CircleCheckIcon",[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["path",{d:"m9 12 2 2 4-4",key:"dzmm74"}]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const I=i("CircleXIcon",[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["path",{d:"m15 9-6 6",key:"1uzhvr"}],["path",{d:"m9 9 6 6",key:"z0biqf"}]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const B=i("InfoIcon",[["circle",{cx:"12",cy:"12",r:"10",key:"1mglay"}],["path",{d:"M12 16v-4",key:"1dtifu"}],["path",{d:"M12 8h.01",key:"e9boi3"}]]),N={key:0,class:"space-y-2"},V={class:"flex items-center gap-2"},F=["onClick"],w=u({__name:"FlashMessages",setup(M){const r=y(),c=k([]),n=f(()=>{const e=[];return r.props.flash.success&&!c.value.includes("success")&&e.push({id:"success",type:"success",text:r.props.flash.success}),r.props.flash.error&&!c.value.includes("error")&&e.push({id:"error",type:"error",text:r.props.flash.error}),r.props.flash.info&&!c.value.includes("info")&&e.push({id:"info",type:"info",text:r.props.flash.info}),e});function p(e){c.value.push(e)}return(e,X)=>n.value.length?(t(),o("div",N,[(t(!0),o(h,null,x(n.value,s=>(t(),o("div",{key:s.id,class:b(["flex items-center justify-between rounded-lg border px-4 py-3 text-sm",s.type==="success"?"border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200":s.type==="info"?"border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200":"border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200"])},[l("span",V,[s.type==="success"?(t(),d(a(z),{key:0,class:"size-4"})):s.type==="info"?(t(),d(a(B),{key:1,class:"size-4"})):(t(),d(a(I),{key:2,class:"size-4"})),_(" "+g(s.text),1)]),l("button",{type:"button",onClick:E=>p(s.id)},[C(a(v),{class:"size-4"})],8,F)],2))),128))])):m("",!0)}});export{z as C,w as _};
