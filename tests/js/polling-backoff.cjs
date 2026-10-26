const vm = require('node:vm'), fs = require('node:fs'), assert = require('node:assert/strict');
const base = require('node:path').resolve(__dirname, '../..') + '/';
const flush = async () => { for (let i=0;i<12;i++) await Promise.resolve(); };
function makeContext(kind) {
    let now=100000, calls=0, response={status:429,ok:false,headers:{get:()=> '60'}};
    const listeners={}, intervals=[];
    const status={textContent:''};
    const node=()=>({textContent:'',classList:{toggle(){}},addEventListener(){},setAttribute(){},appendChild(){},replaceChildren(){},hidden:true});
    const nodes=new Map();
    const root={isConnected:true,dataset:kind==='chat'?{live:'1',lastId:'0',pollUrl:'/messages/1/poll',threadId:'1'}:{userId:'1',pollUrl:'/my-notifications/poll'},
        scrollHeight:0,clientHeight:0,scrollTop:0,querySelector(k){if(!nodes.has(k))nodes.set(k,node());return nodes.get(k);},contains(){return false;}};
    const document={readyState:'complete',hidden:false,visibilityState:'visible',getElementById:id=> id==='chat-messages'?root:id==='chat-live-status'?status:null,
        querySelectorAll:()=>[root],querySelector:()=>null,addEventListener(k,f){(listeners[k]??=[]).push(f);},removeEventListener(){},createElement:()=>node()};
    const context={document,window:{},Notification:undefined,localStorage:{getItem(){return null;}},AbortController,
        Date:class extends Date {static now(){return now;}},setTimeout:()=>1,clearTimeout(){},setInterval:f=>(intervals.push(f),1),clearInterval(){},
        fetch:async()=>{calls++;return response;}};
    vm.createContext(context);
    let source=fs.readFileSync(base+(kind==='chat'?'public/growya-ui/chat.js':'resources/views/components/growya-live-notification-bell.blade.php'),'utf8');
    if(kind==='bell')source=source.match(/<script>([\s\S]*?)<\/script>/)[1];
    vm.runInContext(source,context);
    return {root,status,flush,get calls(){return calls;},tick(ms){now+=ms;intervals.forEach(f=>f());},push(){listeners['growya:push']?.forEach(f=>f({detail:{thread_id:'1'}}));},respond(r){response=r;}};
}
(async()=>{
 for(const kind of ['chat','bell']) {
  const c=makeContext(kind);await flush();assert.equal(c.calls,1,kind+' initial request');
  c.push();c.tick(30000);await flush();assert.equal(c.calls,1,kind+' honors cooldown for both push and interval');
  c.respond({status:200,ok:true,json:async()=>({messages:[],items:[],count:0,latest_id:0})});
  c.tick(31000);await flush();assert.equal(c.calls,2,kind+' resumes after retry-after');
  c.push();await flush();assert.equal(c.calls,2,kind+' coalesces foreground bursts');
  c.tick(8000);await flush();assert.equal(c.calls,3,kind+' resumes normal polling');
  c.root.isConnected=false;c.tick(60000);await flush();assert.equal(c.calls,3,kind+' stops after navigation');
 }
 console.log('PASS: chat and notification polling honor Retry-After, coalesce events, resume, and stop after navigation (10 assertions).');
})().catch(e=>{console.error(e);process.exit(1)});
