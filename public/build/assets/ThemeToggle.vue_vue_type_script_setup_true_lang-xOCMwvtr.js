import{d as i,u as l,b as u,_ as h}from"./LanguageSwitcher.vue_vue_type_script_setup_true_lang-Do-YzB7K.js";import{d as p,o as t,h as _,F as k,b as c,a as g,t as f,u as e,I as y,c as n,w as x,j as b}from"./app-ni-oEpir.js";/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const v=i("MoonIcon",[["path",{d:"M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z",key:"a7tn18"}]]);/**
 * @license lucide-vue-next v0.468.0 - ISC
 *
 * This source code is licensed under the ISC license.
 * See the LICENSE file in the root directory of this source tree.
 */const M=i("SunIcon",[["circle",{cx:"12",cy:"12",r:"4",key:"4exip2"}],["path",{d:"M12 2v2",key:"tus03m"}],["path",{d:"M12 20v2",key:"1lh1kg"}],["path",{d:"m4.93 4.93 1.41 1.41",key:"149t6j"}],["path",{d:"m17.66 17.66 1.41 1.41",key:"ptbguv"}],["path",{d:"M2 12h2",key:"1t8f8n"}],["path",{d:"M20 12h2",key:"1q8mjw"}],["path",{d:"m6.34 17.66-1.41 1.41",key:"1m8zz5"}],["path",{d:"m19.07 4.93-1.41 1.41",key:"1shlcs"}]]),z={class:"flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground"},w={class:"ms-1 grid flex-1 text-start text-sm"},j={class:"mb-0.5 truncate font-semibold leading-none"},A=p({__name:"AppLogo",props:{class:{}},setup(d){const{t:a}=l();return(o,s)=>(t(),_(k,null,[c("div",z,[g(u,{class:"size-5 fill-current text-white dark:text-black"})]),c("div",w,[c("span",j,f(e(a)("brand")),1)])],64))}}),C=p({__name:"ThemeToggle",setup(d){const{appearance:a,updateAppearance:o}=y(),{t:s}=l(),r=b(()=>a.value==="dark"||a.value==="system"&&window.matchMedia("(prefers-color-scheme: dark)").matches);function m(){o(r.value?"light":"dark")}return(B,I)=>(t(),n(e(h),{variant:"ghost",size:"icon",class:"size-8","aria-label":e(s)("nav.themeToggle"),title:e(s)("nav.themeToggle"),onClick:m},{default:x(()=>[r.value?(t(),n(e(M),{key:0,class:"size-4"})):(t(),n(e(v),{key:1,class:"size-4"}))]),_:1},8,["aria-label","title"]))}});export{v as M,M as S,A as _,C as a};
