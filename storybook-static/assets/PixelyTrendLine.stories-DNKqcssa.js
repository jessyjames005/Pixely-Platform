import{F as e,J as t,R as n,_ as r,at as i,g as a,h as o,v as s,vt as c,x as l,y as u}from"./vue.esm-bundler-BoDJd7Xm.js";import{t as d}from"./VCard-CUETpW0b.js";import{t as f}from"./VChip-D7hdcGAj.js";var p={class:`d-flex align-center justify-space-between mb-2`},m={class:`text-caption text-medium-emphasis`},h={class:`text-h5 font-weight-bold mb-2`},g={key:0,class:`text-body-2 text-medium-emphasis mb-2`},_=l({__name:`PixelyTrendLine`,props:{label:{default:`Metric`},value:{default:`0`},subtitle:{default:``},icon:{default:`mdi-chart-line`},iconColor:{default:`#5B5BF5`},iconBackground:{default:`#EDEDFE`},trend:{default:``},trendDirection:{default:`up`},pointColor:{default:`#4CAF50`}},setup(l){let _=i(null);return e(()=>{let e=_.value;if(!e)return;let t=e.getContext(`2d`);if(!t)return;let n=e.parentElement.clientWidth;e.width=n,e.height=96;let r=[15,45,25,65,35,40,55,20,70,30,85,15];t.clearRect(0,0,n,96),t.beginPath(),t.strokeStyle=`#4CAF50`,t.lineWidth=2.5,t.lineCap=`round`,t.lineJoin=`round`;let i=n/(r.length-1);r.forEach((e,n)=>{let r=n*i,a=96-e*1.2;n===0?t.moveTo(r,a):t.lineTo(r,a)}),t.stroke(),r.forEach((e,n)=>{let r=n*i,a=96-e*1.2;t.beginPath(),t.fillStyle=`#4CAF50`,t.arc(r,a,3.5,0,Math.PI*2),t.fill()})}),(e,i)=>(n(),a(d,{class:`d-flex flex-column align-start pa-4`,style:{borderRadius:`16px`,padding:`16px`,backgroundColor:`#FFFFFF`},elevation:`0`,border:``},{default:t(()=>[o(`div`,p,[o(`span`,m,c(l.label),1),l.trend?(n(),a(f,{key:0,size:`x-small`,variant:`flat`,style:{backgroundColor:`#4CAF501A`,color:`#4CAF50`,fontWeight:600}},{default:t(()=>[u(c(l.trend),1)]),_:1})):r(``,!0)]),o(`div`,h,c(l.value),1),l.subtitle?(n(),s(`div`,g,c(l.subtitle),1)):r(``,!0),o(`canvas`,{ref_key:`canvasRef`,ref:_,class:`w-full h-16`,style:{borderRadius:`8px`}},null,512)]),_:1}))}}),v=_;_.__docgenInfo={exportName:`default`,displayName:`PixelyTrendLine`,description:``,tags:{},props:[{name:`label`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'Metric'`}},{name:`value`,required:!1,type:{name:`union`,elements:[{name:`string`},{name:`number`}]},defaultValue:{func:!1,value:`'0'`}},{name:`subtitle`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`''`}},{name:`icon`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'mdi-chart-line'`}},{name:`iconColor`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'#5B5BF5'`}},{name:`iconBackground`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'#EDEDFE'`}},{name:`trend`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`''`}},{name:`trendDirection`,required:!1,type:{name:`union`,elements:[{name:`"up"`},{name:`"down"`},{name:`"flat"`}]},defaultValue:{func:!1,value:`'up'`}},{name:`pointColor`,required:!1,type:{name:`string`},defaultValue:{func:!1,value:`'#4CAF50'`}}],sourceFiles:[`/var/www/resources/js/design-system/components/crm/PixelyTrendLine.vue`]};var y={title:`CRM / PixelyTrendLine`,component:v,tags:[`autodocs`]},b={render:()=>({components:{PixelyTrendLine:v},template:`
      <PixelyTrendLine
        label="Organic Sessions"
        value="89k"
        subtitle="2022"
        trend="+12%"
        trendDirection="up"
        icon="mdi-chart-line"
        iconColor="#4CAF50"
        iconBackground="#E8F5E9"
      />
    `})};b.parameters={...b.parameters,docs:{...b.parameters?.docs,source:{originalSource:`{
  render: () => ({
    components: {
      PixelyTrendLine
    },
    template: \`
      <PixelyTrendLine
        label="Organic Sessions"
        value="89k"
        subtitle="2022"
        trend="+12%"
        trendDirection="up"
        icon="mdi-chart-line"
        iconColor="#4CAF50"
        iconBackground="#E8F5E9"
      />
    \`
  })
}`,...b.parameters?.docs?.source}}};var x=[`Default`];export{b as Default,x as __namedExportsOrder,y as default};