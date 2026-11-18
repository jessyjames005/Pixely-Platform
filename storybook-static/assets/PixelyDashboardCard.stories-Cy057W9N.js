import{h as p,j as f,k as r,l as o,f as y,x as v,z as b,n as x,A as C}from"./vue.esm-bundler-1WDOaPC_.js";import{V as h}from"./VBtn-B3lbe_XD.js";import"./index-9ifeDQX3.js";import"./VIcon-CsjJrb3h.js";import"./loader-uOTrFB-z.js";import"./group-BStXsvvJ.js";const g={class:"pixely-dashboard-card d-flex flex-column align-start pa-4"},w={class:"d-flex align-center justify-space-between mb-2"},D={class:"text-caption text-medium-emphasis text-uppercase font-weight-medium"},P={class:"text-body-2 text-medium-emphasis mb-4"},n=p({__name:"PixelyDashboardCard",props:{title:{default:"Overview"},description:{default:"Summary of your CRM dashboard metrics."}},emits:["action"],setup(s,{emit:c}){const u=c;return(e,i)=>(x(),f("div",g,[r("div",w,[r("span",D,o(s.title),1),y(h,{size:"small",variant:"text",color:"primary",onClick:i[0]||(i[0]=t=>u("action"))},{default:v(()=>{var t;return[C(o((t=e.$t)==null?void 0:t.call(e,"common.action.view_all","View all")),1)]}),_:1})]),r("div",P,o(s.description),1),b(e.$slots,"default")]))}});n.__docgenInfo={exportName:"default",displayName:"PixelyDashboardCard",description:"",tags:{},props:[{name:"title",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'Overview'"}},{name:"description",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'Summary of your CRM dashboard metrics.'"}}],events:[{name:"action"}],slots:[{name:"default"}],sourceFiles:["/var/www/resources/js/design-system/components/crm/PixelyDashboardCard.vue"]};const k={title:"CRM / PixelyDashboardCard",component:n,tags:["autodocs"]},a={render:()=>({components:{PixelyDashboardCard:n},template:`
      <PixelyDashboardCard
        title="Overview"
        description="Summary of your CRM dashboard metrics."
      >
        <div class="row no-gutters mb-8">
          <PixelyStatCardBig
            label="Total Orders"
            value="155k"
            trend="+22%"
            trendDirection="up"
            icon="mdi-cart-outline"
          />
          <PixelyStatCardBig
            label="New Customers"
            value="2,856"
            trend="-8.1%"
            trendDirection="down"
            icon="mdi-account-group-outline"
          />
        </div>
      </PixelyDashboardCard>
    `})};var d,l,m;a.parameters={...a.parameters,docs:{...(d=a.parameters)==null?void 0:d.docs,source:{originalSource:`{
  render: () => ({
    components: {
      PixelyDashboardCard
    },
    template: \`
      <PixelyDashboardCard
        title="Overview"
        description="Summary of your CRM dashboard metrics."
      >
        <div class="row no-gutters mb-8">
          <PixelyStatCardBig
            label="Total Orders"
            value="155k"
            trend="+22%"
            trendDirection="up"
            icon="mdi-cart-outline"
          />
          <PixelyStatCardBig
            label="New Customers"
            value="2,856"
            trend="-8.1%"
            trendDirection="down"
            icon="mdi-account-group-outline"
          />
        </div>
      </PixelyDashboardCard>
    \`
  })
}`,...(m=(l=a.parameters)==null?void 0:l.docs)==null?void 0:m.source}}};const M=["Default"];export{a as Default,M as __namedExportsOrder,k as default};
