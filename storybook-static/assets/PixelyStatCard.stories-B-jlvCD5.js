import{J as e,R as t,_ as n,_t as r,b as i,g as a,h as o,m as s,v as c,vt as l,x as u,y as d}from"./vue.esm-bundler-BoDJd7Xm.js";import{t as f}from"./VAvatar-C2XU--us.js";import{t as p}from"./VCard-CUETpW0b.js";import{t as m}from"./VChip-D7hdcGAj.js";import{t as h}from"./VIcon-C_qOaQYK.js";var g={class:`d-flex align-start justify-space-between mb-3`},_={class:`text-caption text-medium-emphasis`},v={class:`d-flex align-center ga-3`},y={class:`text-h6 font-weight-bold`},b={key:0,class:`text-caption text-medium-emphasis`},x=u({__name:`PixelyStatCard`,props:{label:{},value:{},icon:{default:`mdi-chart-line`},iconColor:{default:`#5B5BF5`},iconBackground:{default:`#EDEDFE`},trend:{},trendDirection:{default:`up`},hint:{}},setup(u){let x=u,S=s(()=>x.trendDirection===`down`?`#EF5350`:x.trendDirection===`flat`?`#9CA3AF`:`#4CAF50`),C=s(()=>x.trendDirection===`down`?`mdi-arrow-down`:x.trendDirection===`flat`?`mdi-minus`:`mdi-arrow-up`);return(s,x)=>(t(),a(p,{class:`pixely-stat-card`,style:{borderRadius:`16px`,padding:`20px`,backgroundColor:`#FFFFFF`},elevation:`0`,border:``},{default:e(()=>[o(`div`,g,[o(`span`,_,l(u.label),1),u.trend?(t(),a(m,{key:0,size:`x-small`,variant:`flat`,style:r({backgroundColor:`${S.value}1A`,color:S.value,fontWeight:600})},{default:e(()=>[i(h,{icon:C.value,size:`12`,start:``},null,8,[`icon`]),d(` `+l(u.trend),1)]),_:1},8,[`style`])):n(``,!0)]),o(`div`,v,[i(f,{rounded:`lg`,size:`44`,style:r({backgroundColor:u.iconBackground})},{default:e(()=>[i(h,{icon:u.icon,color:u.iconColor},null,8,[`icon`,`color`])]),_:1},8,[`style`]),o(`div`,null,[o(`div`,y,l(u.value),1),u.hint?(t(),c(`div`,b,l(u.hint),1)):n(``,!0)])])]),_:1}))}}),S=x;x.__docgenInfo={exportName:`default`,displayName:`PixelyStatCard`,description:``,tags:{},props:[{name:`label`,required:!0,type:{name:`string`}},{name:`value`,required:!0,type:{name:`union`,elements:[{name:`string`},{name:`number`}]}},{name:`icon`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'mdi-chart-line'`}},{name:`iconColor`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'#5B5BF5'`}},{name:`iconBackground`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'#EDEDFE'`}},{name:`trend`,required:!1,type:{name:`string`}},{name:`trendDirection`,required:!1,type:{name:`TrendDirection`},defaultValue:{func:!1,value:`'up'`}},{name:`hint`,required:!1,type:{name:`string`}}],sourceFiles:[`/var/www/resources/js/design-system/components/PixelyStatCard.vue`]};var C={title:`Design System / PixelyStatCard`,component:S,tags:[`autodocs`]},w={render:()=>({components:{PixelyStatCard:S},template:`
      <PixelyStatCard
        label="Total Orders"
        value="155k"
        trend="+22%"
        trendDirection="up"
        hint="Last 4 Month"
        icon="mdi-cart-outline"
        iconColor="#5B5BF5"
        iconBackground="#EDEDFE"
      />
    `})},T={render:()=>({components:{PixelyStatCard:S},template:`
      <PixelyStatCard
        label="New Customers"
        value="2,856"
        trend="-8.1%"
        trendDirection="down"
        icon="mdi-account-group-outline"
        iconColor="#EF5350"
        iconBackground="#FFEBEE"
      />
    `})};w.parameters={...w.parameters,docs:{...w.parameters?.docs,source:{originalSource:`{
  render: () => ({
    components: {
      PixelyStatCard
    },
    template: \`
      <PixelyStatCard
        label="Total Orders"
        value="155k"
        trend="+22%"
        trendDirection="up"
        hint="Last 4 Month"
        icon="mdi-cart-outline"
        iconColor="#5B5BF5"
        iconBackground="#EDEDFE"
      />
    \`
  })
}`,...w.parameters?.docs?.source}}},T.parameters={...T.parameters,docs:{...T.parameters?.docs,source:{originalSource:`{
  render: () => ({
    components: {
      PixelyStatCard
    },
    template: \`
      <PixelyStatCard
        label="New Customers"
        value="2,856"
        trend="-8.1%"
        trendDirection="down"
        icon="mdi-account-group-outline"
        iconColor="#EF5350"
        iconBackground="#FFEBEE"
      />
    \`
  })
}`,...T.parameters?.docs?.source}}};var E=[`Default`,`Declining`];export{T as Declining,w as Default,E as __namedExportsOrder,C as default};