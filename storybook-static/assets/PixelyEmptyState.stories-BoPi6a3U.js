import{J as e,R as t,b as n,g as r,h as i,vt as a,x as o,y as s}from"./vue.esm-bundler-BoDJd7Xm.js";import{t as c}from"./VBtn-CPec6oCN.js";import{t as l}from"./VCard-CUETpW0b.js";import{t as u}from"./VIcon-C_qOaQYK.js";var d={class:`text-h6 font-weight-medium mb-2`},f={class:`text-body-2 text-medium-emphasis mb-6`,style:{"max-width":`320px`,"margin-inline":`auto`}},p=o({__name:`PixelyEmptyState`,props:{icon:{default:`mdi-inbox-outline`},title:{default:`Nothing here yet`},description:{default:`There are no items to display.`},actionLabel:{default:`Create new`}},emits:[`action`],setup(o,{emit:p}){let m=p;return(p,h)=>(t(),r(l,{class:`pixely-empty-state text-center py-12 px-6`,style:{borderRadius:`16px`,backgroundColor:`#F9FAFB`},elevation:`0`},{default:e(()=>[n(u,{icon:o.icon,size:`64`,color:`textMuted`,class:`mb-4`},null,8,[`icon`]),i(`h3`,d,a(o.title),1),i(`p`,f,a(o.description),1),n(c,{color:`primary`,"prepend-icon":`mdi-plus`,onClick:h[0]||=e=>m(`action`)},{default:e(()=>[s(a(o.actionLabel),1)]),_:1})]),_:1}))}}),m=p;p.__docgenInfo={exportName:`default`,displayName:`PixelyEmptyState`,description:``,tags:{},props:[{name:`icon`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'mdi-inbox-outline'`}},{name:`title`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'Nothing here yet'`}},{name:`description`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'There are no items to display.'`}},{name:`actionLabel`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'Create new'`}}],events:[{name:`action`}],sourceFiles:[`/var/www/resources/js/design-system/components/PixelyEmptyState.vue`]};var h={title:`Design System / PixelyEmptyState`,component:m,tags:[`autodocs`]},g={render:()=>({components:{PixelyEmptyState:m},template:`
      <PixelyEmptyState
        title="No movies yet"
        description="Create your first movie and start building your catalogue."
        actionLabel="Add movie"
      />
    `})};g.parameters={...g.parameters,docs:{...g.parameters?.docs,source:{originalSource:`{
  render: () => ({
    components: {
      PixelyEmptyState
    },
    template: \`
      <PixelyEmptyState
        title="No movies yet"
        description="Create your first movie and start building your catalogue."
        actionLabel="Add movie"
      />
    \`
  })
}`,...g.parameters?.docs?.source}}};var _=[`Default`];export{g as Default,_ as __namedExportsOrder,h as default};