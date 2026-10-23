<script>
(() => {
 const form=document.getElementById('checkout-order');if(!form)return;
 const coupon=form.querySelector('[data-coupon-input]'),address=form.querySelector('[name=address_id]'),manual=form.querySelector('#manual-address'),state=form.querySelector('[data-destination-state]'),amount=form.querySelector('[data-quoted-total]'),place=form.querySelector('[data-place-order]'),status=form.querySelector('[data-quote-status]'),badge=form.querySelector('[data-coupon-applied]'),remove=form.querySelector('[data-coupon-remove]'),toast=document.querySelector('[data-checkout-toast]');
 const money=value=>new Intl.NumberFormat('en-IN',{style:'currency',currency:'INR'}).format(value/100);
 let stamp=null,lastQuote=null,controller=null,sequence=0,timeout=null,debounce=null,toastTimer=null;
 const signature=()=>JSON.stringify([address?.value||'',state?.value.trim()||'',coupon.value.trim().toUpperCase()]);
 const message=(text,error=false)=>{status.textContent=text;status.dataset.error=String(error);if(error){toast.textContent=text;toast.hidden=false;clearTimeout(toastTimer);toastTimer=setTimeout(()=>toast.hidden=true,6500)}else{clearTimeout(toastTimer);toast.hidden=true}};
 const invalidate=()=>{sequence++;controller?.abort();clearTimeout(timeout);stamp=null;lastQuote=null;amount.disabled=true;amount.value='';place.textContent='Review final total';place.disabled=false;badge.hidden=true;remove.hidden=!coupon.value;form.querySelectorAll('[data-total]').forEach(node=>{node.textContent='—'});message('Totals need updating. Apply the coupon or click Update total.');};
 const syncAddress=()=>{const saved=Boolean(address?.value);manual.hidden=saved;manual.disabled=saved;};
 const destinationReady=()=>Boolean(address?.value||state.value.trim());
 async function refreshQuote(){
  if(!destinationReady()){message('Enter a delivery state or select a saved address first.',true);return false}
  controller?.abort();controller=new AbortController();const requestController=controller;const current=++sequence,requested=signature();place.disabled=true;message('Checking current prices, coupon and delivery charges…');clearTimeout(timeout);timeout=setTimeout(()=>requestController.abort(),15000);
  try{
   const response=await fetch(form.dataset.quoteUrl,{method:'POST',credentials:'same-origin',signal:requestController.signal,headers:{Accept:'application/json','X-CSRF-TOKEN':form.querySelector('[name=_token]').value},body:new FormData(form)});
   const result=await response.json();if(current!==sequence||requested!==signature())return false;
   if(!response.ok)throw new Error(Object.values(result.errors||{}).flat().join(' ')||result.message||'Unable to calculate total. Please try again.');
   const q=result.data;if(!q||!Number.isSafeInteger(q.total_paise)||q.total_paise<0)throw new Error('The total could not be verified. Please try again.');
   for(const node of form.querySelectorAll('[data-total]')){const key=node.dataset.total;node.textContent=(['discount_paise','product_savings_paise'].includes(key)&&q[key]>0?'− ':'')+money(q[key]??0)}
   stamp=requested;lastQuote=q;amount.value=String(q.total_paise);amount.disabled=false;place.textContent='Place order · '+money(q.total_paise);badge.hidden=!q.coupon_code;badge.textContent=q.coupon_code?'Applied: '+q.coupon_code:'';remove.hidden=!q.coupon_code;message('Final total updated. Review it above, then place your order.');return true;
  }catch(error){if(current!==sequence)return false;stamp=null;lastQuote=null;amount.disabled=true;amount.value='';place.textContent='Review final total';form.querySelectorAll('[data-total="discount_paise"],[data-total="shipping_paise"],[data-total="tax_paise"],[data-total="total_paise"]').forEach(n=>n.textContent='—');badge.hidden=true;message(error.name==='AbortError'?'The total request timed out. Click Update total to try again.':error.message,true);return false}
  finally{if(current===sequence){clearTimeout(timeout);place.disabled=false;}}
 }
 const schedule=()=>{clearTimeout(debounce);debounce=setTimeout(()=>{if(destinationReady())refreshQuote()},550)};
 address?.addEventListener('change',()=>{syncAddress();invalidate();schedule()});state.addEventListener('input',()=>{invalidate();schedule()});coupon.addEventListener('input',()=>{coupon.value=coupon.value.toUpperCase().replace(/[^A-Z0-9_-]/g,'');invalidate()});
 form.querySelector('[data-coupon-apply]').addEventListener('click',()=>{clearTimeout(debounce);refreshQuote()});form.querySelector('[data-refresh-quote]').addEventListener('click',()=>{clearTimeout(debounce);refreshQuote()});remove.addEventListener('click',()=>{coupon.value='';invalidate();refreshQuote()});
 form.addEventListener('submit',async event=>{if(stamp===signature()&&lastQuote&&!amount.disabled){place.disabled=true;place.textContent='Placing order…';return}event.preventDefault();if(!form.reportValidity())return;await refreshQuote()});syncAddress();if(destinationReady())refreshQuote();
})();
</script>
