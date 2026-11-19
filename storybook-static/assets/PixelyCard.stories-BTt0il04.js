import{h as c,v as u,x as p,n as t,j as s,l as a,y as i,z as y}from"./vue.esm-bundler-1WDOaPC_.js";import{V as x}from"./VCard-BGQyxci5.js";import"./VIcon-CsjJrb3h.js";import"./loader-uOTrFB-z.js";import"./VAvatar-CZRbl-Tq.js";import"./index-B9d54lPJ.js";import"./index-9ifeDQX3.js";const h={key:0,class:"mb-4"},g={key:0,class:"text-caption text-medium-emphasis text-uppercase mb-1"},b={key:1,class:"text-h6 font-weight-bold"},f={key:2,class:"text-body-2 text-medium-emphasis"},C={key:1,class:"text-body-2 text-medium-emphasis mb-4"},r=c({__name:"PixelyCard",props:{title:{},subtitle:{},heading:{},description:{}},setup(e){return(m,P)=>(t(),u(x,{class:"pixely-card",style:{borderRadius:"16px",padding:"20px"},elevation:"0",border:""},{default:p(()=>[e.heading||e.title||e.subtitle?(t(),s("header",h,[e.heading?(t(),s("div",g,a(e.heading),1)):i("",!0),e.title?(t(),s("div",b,a(e.title),1)):i("",!0),e.subtitle?(t(),s("div",f,a(e.subtitle),1)):i("",!0)])):i("",!0),e.description?(t(),s("p",C,a(e.description),1)):i("",!0),y(m.$slots,"default")]),_:3}))}});r.__docgenInfo={exportName:"default",displayName:"PixelyCard",description:"",tags:{},props:[{name:"title",required:!1,type:{name:"string"}},{name:"subtitle",required:!1,type:{name:"string"}},{name:"heading",required:!1,type:{name:"string"}},{name:"description",required:!1,type:{name:"string"}}],slots:[{name:"default"}],sourceFiles:["/var/www/resources/js/design-system/components/PixelyCard.vue"]};const B={title:"Design System / PixelyCard",component:r,tags:["autodocs"]},n={render:()=>({components:{PixelyCard:r},template:`
      <PixelyCard title="Card Title" subtitle="Subtitle text" heading="Section Header" description="This is a standard Pixely design system card.">
        <p class="text-body-2 text-medium-emphasis">Card body content goes here. Use this container to group related information.</p>
      </PixelyCard>
    `})};var d,o,l;n.parameters={...n.parameters,docs:{...(d=n.parameters)==null?void 0:d.docs,source:{originalSource:`{
  render: () => ({
    components: {
      PixelyCard
    },
    template: \`
      <PixelyCard title="Card Title" subtitle="Subtitle text" heading="Section Header" description="This is a standard Pixely design system card.">
        <p class="text-body-2 text-medium-emphasis">Card body content goes here. Use this container to group related information.</p>
      </PixelyCard>
    \`
  })
}`,...(l=(o=n.parameters)==null?void 0:o.docs)==null?void 0:l.source}}};const N=["Default"];export{n as Default,N as __namedExportsOrder,B as default};
