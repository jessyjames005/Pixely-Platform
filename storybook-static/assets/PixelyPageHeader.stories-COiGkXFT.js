import{h as y,j as l,k as a,f as o,x as r,l as n,z as v,y as x,n as d,A as b}from"./vue.esm-bundler-1WDOaPC_.js";import{V as P}from"./VAvatar-CZRbl-Tq.js";import{V as h}from"./VIcon-CsjJrb3h.js";import{V}from"./VBtn-B3lbe_XD.js";import"./loader-uOTrFB-z.js";import"./index-B9d54lPJ.js";import"./index-9ifeDQX3.js";import"./group-BStXsvvJ.js";const w={class:"pixely-page-header d-flex align-center justify-space-between flex-wrap ga-3 mb-6"},_={class:"d-flex align-center ga-3"},A={class:"text-h5 font-weight-bold mb-1"},H={class:"text-body-2 text-medium-emphasis mb-0"},M={key:0,class:"d-flex ga-2"},s=y({__name:"PixelyPageHeader",props:{title:{default:"Page title"},subtitle:{default:"Manage your platform resources"},icon:{default:"mdi-view-dashboard"},actionLabel:{default:"Add"},showAction:{type:Boolean,default:!0}},emits:["action"],setup(e,{emit:f}){const p=f;return(g,i)=>(d(),l("div",w,[a("div",_,[o(P,{rounded:"lg",size:"48",style:{backgroundColor:"#EDEDFE",color:"#5B5BF5"}},{default:r(()=>[o(h,{icon:e.icon},null,8,["icon"])]),_:1}),a("div",null,[a("h1",A,n(e.title),1),a("p",H,n(e.subtitle),1)])]),e.showAction?(d(),l("div",M,[v(g.$slots,"actions",{},()=>[o(V,{color:"primary","prepend-icon":"mdi-plus",onClick:i[0]||(i[0]=B=>p("action"))},{default:r(()=>[b(n(e.actionLabel),1)]),_:1})])])):x("",!0)]))}});s.__docgenInfo={exportName:"default",displayName:"PixelyPageHeader",description:"",tags:{},props:[{name:"title",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'Page title'"}},{name:"subtitle",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'Manage your platform resources'"}},{name:"icon",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'mdi-view-dashboard'"}},{name:"actionLabel",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'Add'"}},{name:"showAction",required:!1,type:{name:"boolean"},defaultValue:{func:!1,value:"true"}}],events:[{name:"action"}],slots:[{name:"actions"}],sourceFiles:["/var/www/resources/js/design-system/components/PixelyPageHeader.vue"]};const j={title:"Design System / PixelyPageHeader",component:s,tags:["autodocs"]},t={render:()=>({components:{PixelyPageHeader:s},template:`
      <PixelyPageHeader
        title="Movie Catalogue"
        subtitle="Manage your movies, genres, and translations"
        icon="mdi-movie-open-outline"
        actionLabel="Add Movie"
      />
    `})};var c,u,m;t.parameters={...t.parameters,docs:{...(c=t.parameters)==null?void 0:c.docs,source:{originalSource:`{
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
}`,...(m=(u=t.parameters)==null?void 0:u.docs)==null?void 0:m.source}}};const F=["Default"];export{t as Default,F as __namedExportsOrder,j as default};
