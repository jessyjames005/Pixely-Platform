import{h as p,v as y,x as n,n as f,f as s,k as r,l as a,A as x}from"./vue.esm-bundler-1WDOaPC_.js";import{V as g}from"./VCard-BGQyxci5.js";import{V as v}from"./VIcon-CsjJrb3h.js";import{V as b}from"./VBtn-B3lbe_XD.js";import"./loader-uOTrFB-z.js";import"./VAvatar-CZRbl-Tq.js";import"./index-B9d54lPJ.js";import"./index-9ifeDQX3.js";import"./group-BStXsvvJ.js";const h={class:"text-h6 font-weight-medium mb-2"},V={class:"text-body-2 text-medium-emphasis mb-6",style:{"max-width":"320px","margin-inline":"auto"}},i=p({__name:"PixelyEmptyState",props:{icon:{default:"mdi-inbox-outline"},title:{default:"Nothing here yet"},description:{default:"There are no items to display."},actionLabel:{default:"Create new"}},emits:["action"],setup(e,{emit:d}){const u=d;return(S,o)=>(f(),y(g,{class:"pixely-empty-state text-center py-12 px-6",style:{borderRadius:"16px",backgroundColor:"#F9FAFB"},elevation:"0"},{default:n(()=>[s(v,{icon:e.icon,size:"64",color:"textMuted",class:"mb-4"},null,8,["icon"]),r("h3",h,a(e.title),1),r("p",V,a(e.description),1),s(b,{color:"primary","prepend-icon":"mdi-plus",onClick:o[0]||(o[0]=C=>u("action"))},{default:n(()=>[x(a(e.actionLabel),1)]),_:1})]),_:1}))}});i.__docgenInfo={exportName:"default",displayName:"PixelyEmptyState",description:"",tags:{},props:[{name:"icon",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'mdi-inbox-outline'"}},{name:"title",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'Nothing here yet'"}},{name:"description",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'There are no items to display.'"}},{name:"actionLabel",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'Create new'"}}],events:[{name:"action"}],sourceFiles:["/var/www/resources/js/design-system/components/PixelyEmptyState.vue"]};const A={title:"Design System / PixelyEmptyState",component:i,tags:["autodocs"]},t={render:()=>({components:{PixelyEmptyState:i},template:`
      <PixelyEmptyState
        title="No movies yet"
        description="Create your first movie and start building your catalogue."
        actionLabel="Add movie"
      />
    `})};var l,m,c;t.parameters={...t.parameters,docs:{...(l=t.parameters)==null?void 0:l.docs,source:{originalSource:`{
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
}`,...(c=(m=t.parameters)==null?void 0:m.docs)==null?void 0:c.source}}};const D=["Default"];export{t as Default,D as __namedExportsOrder,A as default};
