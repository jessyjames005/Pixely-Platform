import{h as m,v as u,x as n,n as x,k as o,l as r,f as l,A as f}from"./vue.esm-bundler-1WDOaPC_.js";import{V as y}from"./VCard-BGQyxci5.js";import{V as g}from"./VBtn-B3lbe_XD.js";import{V as v}from"./VIcon-CsjJrb3h.js";import"./loader-uOTrFB-z.js";import"./VAvatar-CZRbl-Tq.js";import"./index-B9d54lPJ.js";import"./index-9ifeDQX3.js";import"./group-BStXsvvJ.js";const k={class:"d-flex align-center justify-space-between px-3 py-2 border-b border-opacity-10 border-white"},C={class:"text-caption text-medium-emphasis"},B={class:"pa-3 text-body-2 text-white",style:{margin:"0","overflow-x":"auto"}},s=m({__name:"PixelyCodeBlock",props:{code:{default:""},language:{default:"javascript"}},setup(a){return(p,e)=>(x(),u(y,{class:"pixely-code-block",style:{borderRadius:"12px",backgroundColor:"#1F2A44"},elevation:"0"},{default:n(()=>[o("div",k,[o("span",C,r(a.language),1),l(g,{size:"x-small",variant:"text",color:"white",onClick:e[0]||(e[0]=V=>p.navigator.clipboard.writeText(a.code))},{default:n(()=>[l(v,{size:"14",icon:"mdi-content-copy"}),e[1]||(e[1]=f(" Copy ",-1))]),_:1})]),o("pre",B,[o("code",null,r(a.code),1)])]),_:1}))}});s.__docgenInfo={exportName:"default",displayName:"PixelyCodeBlock",description:"",tags:{},props:[{name:"code",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"''"}},{name:"language",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'javascript'"}}],sourceFiles:["/var/www/resources/js/design-system/components/PixelyCodeBlock.vue"]};const q={title:"Design System / PixelyCodeBlock",component:s,tags:["autodocs"]},t={render:()=>({components:{PixelyCodeBlock:s},template:`
      <PixelyCodeBlock
        code="const hello = 'Pixely Design System'"
        language="javascript"
      />
    `})};var i,c,d;t.parameters={...t.parameters,docs:{...(i=t.parameters)==null?void 0:i.docs,source:{originalSource:`{
  render: () => ({
    components: {
      PixelyCodeBlock
    },
    template: \`
      <PixelyCodeBlock
        code="const hello = 'Pixely Design System'"
        language="javascript"
      />
    \`
  })
}`,...(d=(c=t.parameters)==null?void 0:c.docs)==null?void 0:d.source}}};const z=["Default"];export{t as Default,z as __namedExportsOrder,q as default};
