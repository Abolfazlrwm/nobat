import{d as o,u as l,b as h,_ as u}from"./LanguageSwitcher.vue_vue_type_script_setup_true_lang-COUCpJ5s.js";import{d,o as t,h as _,F as k,b as c,a as y,t as f,u as e,I as g,c as n,w as b,j as v}from"./app-BSRpQoXN.js";/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const x=o("MoonIcon",[["path",{d:"M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z",key:"a7tn18"}]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const M=o("SunIcon",[["circle",{cx:"12",cy:"12",r:"4",key:"4exip2"}],["path",{d:"M12 2v2",key:"tus03m"}],["path",{d:"M12 20v2",key:"1lh1kg"}],["path",{d:"m4.93 4.93 1.41 1.41",key:"149t6j"}],["path",{d:"m17.66 17.66 1.41 1.41",key:"ptbguv"}],["path",{d:"M2 12h2",key:"1t8f8n"}],["path",{d:"M20 12h2",key:"1q8mjw"}],["path",{d:"m6.34 17.66-1.41 1.41",key:"1m8zz5"}],["path",{d:"m19.07 4.93-1.41 1.41",key:"1shlcs"}]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const A=o("XIcon",[["path",{d:"M18 6 6 18",key:"1bl5f8"}],["path",{d:"m6 6 12 12",key:"d8bk6v"}]]),z={class:"flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"},w={class:"ms-1 grid flex-1 text-start text-sm"},I={class:"mb-0.5 truncate font-semibold leading-none"},C=d({__name:"AppLogo",props:{class:{}},setup(p){const{t:a}=l();return(r,s)=>(t(),_(k,null,[c("div",z,[y(h,{class:"size-5 fill-current text-white dark:text-black"})]),c("div",w,[c("span",I,f(e(a)("brand")),1)])],64))}}),L=d({__name:"ThemeToggle",setup(p){const{appearance:a,updateAppearance:r}=g(),{t:s}=l(),i=v(()=>a.value==="dark"||a.value==="system"&&window.matchMedia("(prefers-color-scheme: dark)").matches);function m(){r(i.value?"light":"dark")}return(j,B)=>(t(),n(e(u),{variant:"ghost",size:"icon",class:"size-8","aria-label":e(s)("nav.themeToggle"),title:e(s)("nav.themeToggle"),onClick:m},{default:b(()=>[i.value?(t(),n(e(M),{key:0,class:"size-4"})):(t(),n(e(x),{key:1,class:"size-4"}))]),_:1},8,["aria-label","title"]))}});export{x as M,M as S,A as X,C as _,L as a};
