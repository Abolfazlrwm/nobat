import{d as u,G as b,o as s,h as i,F as g,k as x,n as m,b as c,c as h,p as f,t as v,u as a,w as d,a as t,m as M,j as w}from"./app-ChIviAzc.js";import{d as o,u as I}from"./LanguageSwitcher.vue_vue_type_script_setup_true_lang-Cos1Sfve.js";import{_ as C,a as $}from"./Layout.vue_vue_type_script_setup_true_lang-C6UhK5cz.js";import{_ as B}from"./AppLayout.vue_vue_type_script_setup_true_lang-7Nl_tP-L.js";import"./Separator.vue_vue_type_script_setup_true_lang-Di4OmNx3.js";import"./index-7Hlf8ZMW.js";import"./AppLogo.vue_vue_type_script_setup_true_lang-jBMqJjhA.js";import"./users-DzWEmhe6.js";import"./hourglass-Dk1n5zph.js";/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const A=o("MonitorIcon",[["rect",{width:"20",height:"14",x:"2",y:"3",rx:"2",key:"48i651"}],["line",{x1:"8",x2:"16",y1:"21",y2:"21",key:"1svkeh"}],["line",{x1:"12",x2:"12",y1:"17",y2:"21",key:"vw1qmm"}]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const D=o("MoonIcon",[["path",{d:"M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z",key:"a7tn18"}]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const L=o("SunIcon",[["circle",{cx:"12",cy:"12",r:"4",key:"4exip2"}],["path",{d:"M12 2v2",key:"tus03m"}],["path",{d:"M12 20v2",key:"1lh1kg"}],["path",{d:"m4.93 4.93 1.41 1.41",key:"149t6j"}],["path",{d:"m17.66 17.66 1.41 1.41",key:"ptbguv"}],["path",{d:"M2 12h2",key:"1t8f8n"}],["path",{d:"M20 12h2",key:"1q8mjw"}],["path",{d:"m6.34 17.66-1.41 1.41",key:"1m8zz5"}],["path",{d:"m19.07 4.93-1.41 1.41",key:"1shlcs"}]]),S=["onClick"],j={class:"ml-1.5 text-sm"},z=u({__name:"AppearanceTabs",props:{class:{default:""}},setup(l){const{appearance:e,updateAppearance:n}=b(),p=[{value:"light",Icon:L,label:"Light"},{value:"dark",Icon:D,label:"Dark"},{value:"system",Icon:A,label:"System"}];return(k,q)=>(s(),i("div",{class:m(["inline-flex gap-1 rounded-lg bg-neutral-100 p-1 dark:bg-neutral-800",l.class])},[(s(),i(g,null,x(p,({value:r,Icon:_,label:y})=>c("button",{key:r,onClick:F=>a(n)(r),class:m(["flex items-center rounded-md px-3.5 py-1.5 transition-colors",a(e)===r?"bg-white shadow-sm dark:bg-neutral-700 dark:text-neutral-100":"text-neutral-500 hover:bg-neutral-200/60 hover:text-black dark:text-neutral-400 dark:hover:bg-neutral-700/60"])},[(s(),h(f(_),{class:"-ml-1 h-4 w-4"})),c("span",j,v(y),1)],10,S)),64))],2))}}),T={class:"space-y-6"},O=u({__name:"Appearance",setup(l){const{t:e}=I(),n=w(()=>[{title:e("panel.settings.appearanceBreadcrumb"),href:"/settings/appearance"}]);return(p,k)=>(s(),h(B,{breadcrumbs:n.value},{default:d(()=>[t(a(M),{title:a(e)("panel.settings.appearancePageTitle")},null,8,["title"]),t(C,null,{default:d(()=>[c("div",T,[t($,{title:a(e)("panel.settings.appearanceTitle"),description:a(e)("panel.settings.appearanceDesc")},null,8,["title","description"]),t(z)])]),_:1})]),_:1},8,["breadcrumbs"]))}});export{O as default};
