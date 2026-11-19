import{J as e,R as t,V as n,b as r,h as i,v as a,vt as o,x as s,y as c}from"./vue.esm-bundler-BoDJd7Xm.js";import{t as l}from"./VBtn-CPec6oCN.js";var u={class:`pixely-dashboard-card d-flex flex-column align-start pa-4`},d={class:`d-flex align-center justify-space-between mb-2`},f={class:`text-caption text-medium-emphasis text-uppercase font-weight-medium`},p={class:`text-body-2 text-medium-emphasis mb-4`},m=s({__name:`PixelyDashboardCard`,props:{title:{default:`Overview`},description:{default:`Summary of your CRM dashboard metrics.`}},emits:[`action`],setup(s,{emit:m}){let h=m;return(m,g)=>(t(),a(`div`,u,[i(`div`,d,[i(`span`,f,o(s.title),1),r(l,{size:`small`,variant:`text`,color:`primary`,onClick:g[0]||=e=>h(`action`)},{default:e(()=>[c(o(m.$t?.(`common.action.view_all`,`View all`)),1)]),_:1})]),i(`div`,p,o(s.description),1),n(m.$slots,`default`)]))}}),h=m;m.__docgenInfo={exportName:`default`,displayName:`PixelyDashboardCard`,description:``,tags:{},props:[{name:`title`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'Overview'`}},{name:`description`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'Summary of your CRM dashboard metrics.'`}}],events:[{name:`action`}],slots:[{name:`default`}],sourceFiles:[`/var/www/resources/js/design-system/components/crm/PixelyDashboardCard.vue`]};var g={title:`CRM / PixelyDashboardCard`,component:h,tags:[`autodocs`]},_={render:()=>({components:{PixelyDashboardCard:h},template:`
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
    `})};_.parameters={..._.parameters,docs:{..._.parameters?.docs,source:{originalSource:`{
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
}`,..._.parameters?.docs?.source}}};var v=[`Default`];export{_ as Default,v as __namedExportsOrder,g as default};