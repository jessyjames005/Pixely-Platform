import{h as k,o as V,n as m,v as h,x,k as o,l as s,A as E,y as v,j as B,g as D}from"./vue.esm-bundler-1WDOaPC_.js";import{V as P}from"./VChip-Ch_IghGA.js";import{V as T}from"./VCard-BGQyxci5.js";import"./index-BepWvI7E.js";import"./VIcon-CsjJrb3h.js";import"./loader-uOTrFB-z.js";import"./group-BStXsvvJ.js";import"./index-9ifeDQX3.js";import"./VAvatar-CZRbl-Tq.js";import"./index-B9d54lPJ.js";const _={class:"d-flex align-center justify-space-between mb-2"},w={class:"text-caption text-medium-emphasis"},A={class:"text-h5 font-weight-bold mb-2"},q={key:0,class:"text-body-2 text-medium-emphasis mb-2"},p=k({__name:"PixelyTrendLine",props:{label:{default:"Metric"},value:{default:"0"},subtitle:{default:""},icon:{default:"mdi-chart-line"},iconColor:{default:"#5B5BF5"},iconBackground:{default:"#EDEDFE"},trend:{default:""},trendDirection:{default:"up"},pointColor:{default:"#4CAF50"}},setup(n){const g=D(null);return V(()=>{const t=g.value;if(!t)return;const e=t.getContext("2d");if(!e)return;const c=t.parentElement.clientWidth,a=96;t.width=c,t.height=a;const d=[15,45,25,65,35,40,55,20,70,30,85,15];e.clearRect(0,0,c,a),e.beginPath(),e.strokeStyle="#4CAF50",e.lineWidth=2.5,e.lineCap="round",e.lineJoin="round";const y=c/(d.length-1);d.forEach((f,l)=>{const r=l*y,i=a-f*1.2;l===0?e.moveTo(r,i):e.lineTo(r,i)}),e.stroke(),d.forEach((f,l)=>{const r=l*y,i=a-f*1.2;e.beginPath(),e.fillStyle="#4CAF50",e.arc(r,i,3.5,0,Math.PI*2),e.fill()})}),(t,e)=>(m(),h(T,{class:"d-flex flex-column align-start pa-4",style:{borderRadius:"16px",padding:"16px",backgroundColor:"#FFFFFF"},elevation:"0",border:""},{default:x(()=>[o("div",_,[o("span",w,s(n.label),1),n.trend?(m(),h(P,{key:0,size:"x-small",variant:"flat",style:{backgroundColor:"#4CAF501A",color:"#4CAF50",fontWeight:600}},{default:x(()=>[E(s(n.trend),1)]),_:1})):v("",!0)]),o("div",A,s(n.value),1),n.subtitle?(m(),B("div",q,s(n.subtitle),1)):v("",!0),o("canvas",{ref_key:"canvasRef",ref:g,class:"w-full h-16",style:{borderRadius:"8px"}},null,512)]),_:1}))}});p.__docgenInfo={exportName:"default",displayName:"PixelyTrendLine",description:"",tags:{},props:[{name:"label",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'Metric'"}},{name:"value",required:!1,type:{name:"union",elements:[{name:"string"},{name:"number"}]},defaultValue:{func:!1,value:"'0'"}},{name:"subtitle",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"''"}},{name:"icon",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'mdi-chart-line'"}},{name:"iconColor",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'#5B5BF5'"}},{name:"iconBackground",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'#EDEDFE'"}},{name:"trend",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"''"}},{name:"trendDirection",required:!1,type:{name:"union",elements:[{name:'"up"'},{name:'"down"'},{name:'"flat"'}]},defaultValue:{func:!1,value:"'up'"}},{name:"pointColor",required:!1,type:{name:"string"},defaultValue:{func:!1,value:"'#4CAF50'"}}],sourceFiles:["/var/www/resources/js/design-system/components/crm/PixelyTrendLine.vue"]};const J={title:"CRM / PixelyTrendLine",component:p,tags:["autodocs"]},u={render:()=>({components:{PixelyTrendLine:p},template:`
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
    `})};var b,C,F;u.parameters={...u.parameters,docs:{...(b=u.parameters)==null?void 0:b.docs,source:{originalSource:`{
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
}`,...(F=(C=u.parameters)==null?void 0:C.docs)==null?void 0:F.source}}};const $=["Default"];export{u as Default,$ as __namedExportsOrder,J as default};
