import{J as e,R as t,V as n,_ as r,b as i,h as a,v as o,vt as s,x as c,y as l}from"./vue.esm-bundler-BoDJd7Xm.js";import{t as u}from"./VAvatar-C2XU--us.js";import{t as d}from"./VBtn-CPec6oCN.js";import{t as f}from"./VIcon-C_qOaQYK.js";var p={class:`pixely-page-header d-flex align-center justify-space-between flex-wrap ga-3 mb-6`},m={class:`d-flex align-center ga-3`},h={class:`text-h5 font-weight-bold mb-1`},g={class:`text-body-2 text-medium-emphasis mb-0`},_={key:0,class:`d-flex ga-2`},v=c({__name:`PixelyPageHeader`,props:{title:{default:`Page title`},subtitle:{default:`Manage your platform resources`},icon:{default:`mdi-view-dashboard`},actionLabel:{default:`Add`},showAction:{type:Boolean,default:!0}},emits:[`action`],setup(c,{emit:v}){let y=v;return(v,b)=>(t(),o(`div`,p,[a(`div`,m,[i(u,{rounded:`lg`,size:`48`,style:{backgroundColor:`#EDEDFE`,color:`#5B5BF5`}},{default:e(()=>[i(f,{icon:c.icon},null,8,[`icon`])]),_:1}),a(`div`,null,[a(`h1`,h,s(c.title),1),a(`p`,g,s(c.subtitle),1)])]),c.showAction?(t(),o(`div`,_,[n(v.$slots,`actions`,{},()=>[i(d,{color:`primary`,"prepend-icon":`mdi-plus`,onClick:b[0]||=e=>y(`action`)},{default:e(()=>[l(s(c.actionLabel),1)]),_:1})])])):r(``,!0)]))}}),y=v;v.__docgenInfo={exportName:`default`,displayName:`PixelyPageHeader`,description:``,tags:{},props:[{name:`title`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'Page title'`}},{name:`subtitle`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'Manage your platform resources'`}},{name:`icon`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'mdi-view-dashboard'`}},{name:`actionLabel`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'Add'`}},{name:`showAction`,required:!1,type:{name:`boolean`},defaultValue:{func:!1,value:`true`}}],events:[{name:`action`}],slots:[{name:`actions`}],sourceFiles:[`/var/www/resources/js/design-system/components/PixelyPageHeader.vue`]};var b={title:`Design System / PixelyPageHeader`,component:y,tags:[`autodocs`]},x={render:()=>({components:{PixelyPageHeader:y},template:`
      <PixelyPageHeader
        title="Movie Catalogue"
        subtitle="Manage your movies, genres, and translations"
        icon="mdi-movie-open-outline"
        actionLabel="Add Movie"
      />
    `})};x.parameters={...x.parameters,docs:{...x.parameters?.docs,source:{originalSource:`{
  render: () => ({
    components: {
      PixelyPageHeader
    },
    template: \`
      <PixelyPageHeader
        title="Movie Catalogue"
        subtitle="Manage your movies, genres, and translations"
        icon="mdi-movie-open-outline"
        actionLabel="Add Movie"
      />
    \`
  })
}`,...x.parameters?.docs?.source}}};var S=[`Default`];export{x as Default,S as __namedExportsOrder,b as default};