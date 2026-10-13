// Focused asynchronous confirmation behavior; no browser, services or database.
const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict'),path=require('node:path');
const code=fs.readFileSync(path.join(__dirname,'../../public/js/confirm-item-removal.js'),'utf8');
let checks=0;function check(v,m){assert.ok(v,m);checks++;}
(async()=>{
 let resolve,prompts=0,removals=0;
 const row={isConnected:true},window={RNTranslate:k=>k,Swal:{fire:()=>{prompts++;return new Promise(r=>{resolve=r;});}}};
 vm.runInNewContext(code,{window,WeakSet});
 const first=window.RNConfirmItemRemoval(row,()=>removals++);
 await window.RNConfirmItemRemoval(row,()=>removals++);
 check(prompts===1,'Repeated click opened another warning');check(removals===0,'Removed while confirmation pending');
 resolve({isConfirmed:false});await first;check(removals===0,'Cancel removed row');
 const next=window.RNConfirmItemRemoval(row,()=>{removals++;row.isConnected=false;});resolve({isConfirmed:true});await next;
 check(removals===1,'Confirmed removal did not run once');await window.RNConfirmItemRemoval(row,()=>removals++);check(removals===1,'Detached row removed twice');
 row.isConnected=true;const detached=window.RNConfirmItemRemoval(row,()=>removals++);row.isConnected=false;resolve({isConfirmed:true});await detached;check(removals===1,'Delayed confirmation mutated detached row');
 window.Swal=null;row.isConnected=true;window.confirm=()=>false;await window.RNConfirmItemRemoval(row,()=>removals++);check(removals===1,'Native fallback cancel removed row');
 window.confirm=()=>true;await window.RNConfirmItemRemoval(row,()=>removals++);check(removals===2,'Native fallback confirm failed');
 console.log(JSON.stringify({checks,pass:true,type:'isolated confirmation behavior'}));
})().catch(e=>{console.error(e);process.exitCode=1;});
