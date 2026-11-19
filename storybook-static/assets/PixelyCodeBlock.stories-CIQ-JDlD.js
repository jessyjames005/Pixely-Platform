import{J as e,R as t,b as n,g as r,h as i,vt as a,x as o,y as s}from"./vue.esm-bundler-BoDJd7Xm.js";import{t as c}from"./VBtn-CPec6oCN.js";import{t as l}from"./VCard-CUETpW0b.js";import{t as u}from"./VIcon-C_qOaQYK.js";var d={class:`d-flex align-center justify-space-between px-3 py-2 border-b border-opacity-10 border-white`},f={class:`text-caption text-medium-emphasis`},p={class:`pa-3 text-body-2 text-white`,style:{margin:`0`,"overflow-x":`auto`}},m=o({__name:`PixelyCodeBlock`,props:{code:{default:``},language:{default:`javascript`}},setup(o){return(m,h)=>(t(),r(l,{class:`pixely-code-block`,style:{borderRadius:`12px`,backgroundColor:`#1F2A44`},elevation:`0`},{default:e(()=>[i(`div`,d,[i(`span`,f,a(o.language),1),n(c,{size:`x-small`,variant:`text`,color:`white`,onClick:h[0]||=e=>m.navigator.clipboard.writeText(o.code)},{default:e(()=>[n(u,{size:`14`,icon:`mdi-content-copy`}),h[1]||=s(` Copy `,-1)]),_:1})]),i(`pre`,p,[i(`code`,null,a(o.code),1)])]),_:1}))}}),h=m;m.__docgenInfo={exportName:`default`,displayName:`PixelyCodeBlock`,description:``,tags:{},props:[{name:`code`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`''`}},{name:`language`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'javascript'`}}],sourceFiles:[`/var/www/resources/js/design-system/components/PixelyCodeBlock.vue`]};var g={title:`Design System / PixelyCodeBlock`,component:h,tags:[`autodocs`]},_={render:()=>({components:{PixelyCodeBlock:h},template:`
      <PixelyCodeBlock
        code="const hello = 'Pixely Design System'"
        language="javascript"
      />
    `})};_.parameters={..._.parameters,docs:{..._.parameters?.docs,source:{originalSource:`{
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
}`,..._.parameters?.docs?.source}}};var v=[`Default`];export{_ as Default,v as __namedExportsOrder,g as default};