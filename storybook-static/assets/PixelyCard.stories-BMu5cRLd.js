import{J as e,R as t,V as n,_ as r,g as i,v as a,vt as o,x as s}from"./vue.esm-bundler-BoDJd7Xm.js";import{t as c}from"./VCard-CUETpW0b.js";var l={key:0,class:`mb-4`},u={key:0,class:`text-caption text-medium-emphasis text-uppercase mb-1`},d={key:1,class:`text-h6 font-weight-bold`},f={key:2,class:`text-body-2 text-medium-emphasis`},p={key:1,class:`text-body-2 text-medium-emphasis mb-4`},m=s({__name:`PixelyCard`,props:{title:{},subtitle:{},heading:{},description:{}},setup(s){return(m,h)=>(t(),i(c,{class:`pixely-card`,style:{borderRadius:`16px`,padding:`20px`},elevation:`0`,border:``},{default:e(()=>[s.heading||s.title||s.subtitle?(t(),a(`header`,l,[s.heading?(t(),a(`div`,u,o(s.heading),1)):r(``,!0),s.title?(t(),a(`div`,d,o(s.title),1)):r(``,!0),s.subtitle?(t(),a(`div`,f,o(s.subtitle),1)):r(``,!0)])):r(``,!0),s.description?(t(),a(`p`,p,o(s.description),1)):r(``,!0),n(m.$slots,`default`)]),_:3}))}}),h=m;m.__docgenInfo={exportName:`default`,displayName:`PixelyCard`,description:``,tags:{},props:[{name:`title`,required:!1,type:{name:`string`}},{name:`subtitle`,required:!1,type:{name:`string`}},{name:`heading`,required:!1,type:{name:`string`}},{name:`description`,required:!1,type:{name:`string`}}],slots:[{name:`default`}],sourceFiles:[`/var/www/resources/js/design-system/components/PixelyCard.vue`]};var g={title:`Design System / PixelyCard`,component:h,tags:[`autodocs`]},_={render:()=>({components:{PixelyCard:h},template:`
      <PixelyCard title="Card Title" subtitle="Subtitle text" heading="Section Header" description="This is a standard Pixely design system card.">
        <p class="text-body-2 text-medium-emphasis">Card body content goes here. Use this container to group related information.</p>
      </PixelyCard>
    `})};_.parameters={..._.parameters,docs:{..._.parameters?.docs,source:{originalSource:`{
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
}`,..._.parameters?.docs?.source}}};var v=[`Default`];export{_ as Default,v as __namedExportsOrder,g as default};