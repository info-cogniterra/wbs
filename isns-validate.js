(function(){
  var form = document.querySelector('form[action$="/send-isns.php"]') || document.getElementById('isnsForm');
  if(!form) return;

  var get = function(n){ return form.querySelector('[name="'+n+'"]'); };
  var fields = { sluzba:get('sluzba'), jmeno:get('jmeno'), telefon:get('telefon'), email:get('email'), zprava:get('zprava') };

  function toast(text, ok){
    var box = document.getElementById('isnsToast');
    if(!box){
      box = document.createElement('div');
      box.id='isnsToast';
      box.style.position='fixed'; box.style.right='16px'; box.style.bottom='16px'; box.style.zIndex='9999';
      box.style.maxWidth='420px'; box.style.padding='12px 14px'; box.style.borderRadius='12px'; box.style.boxShadow='0 8px 24px rgba(0,0,0,.15)';
      box.style.fontFamily='system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif'; box.style.color='#fff'; box.style.background='#0b875b';
      var close = document.createElement('button'); close.textContent='×'; close.style.float='right'; close.style.background='transparent';
      close.style.border='0'; close.style.color='#fff'; close.style.fontSize='20px'; close.style.cursor='pointer';
      close.onclick=function(){ box.remove(); }; box.appendChild(close);
      var span=document.createElement('div'); span.id='isnsToastText'; box.appendChild(span);
      document.body.appendChild(box);
    }
    box.style.background = ok ? '#0b875b' : '#b91c1c';
    document.getElementById('isnsToastText').textContent = text;
    box.style.display='block';
  }

  function setErr(el, show, msg){
    if(!el) return;
    el.style.borderColor = show ? '#b91c1c' : '';
    var m = el.parentElement.querySelector('.isns-err');
    if(!m){ m=document.createElement('div'); m.className='isns-err'; m.style.color='#b91c1c'; m.style.fontSize='12px'; m.style.marginTop='6px'; el.parentElement.appendChild(m); }
    m.textContent = show ? (msg || 'Toto pole je povinné.') : '';
    m.style.display = show ? 'block' : 'none';
  }

  function validate(){
    var ok=true; var emailRe=/^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if(!fields.sluzba || !fields.sluzba.value.trim()){ setErr(fields.sluzba,true,'Vyberte službu.'); ok=false; } else setErr(fields.sluzba,false);
    if(!fields.jmeno || !fields.jmeno.value.trim()){ setErr(fields.jmeno,true,'Vyplňte jméno.'); ok=false; } else setErr(fields.jmeno,false);
    if(!fields.telefon || !fields.telefon.value.trim()){ setErr(fields.telefon,true,'Vyplňte telefon.'); ok=false; } else setErr(fields.telefon,false);
    if(!fields.email || !emailRe.test(fields.email.value.trim())){ setErr(fields.email,true,'Zadejte platný e‑mail.'); ok=false; } else setErr(fields.email,false);
    return ok;
  }

  form.addEventListener('submit', function(e){
    if(!validate()){ e.preventDefault(); toast('Zkontrolujte označená pole a zkuste to znovu.', false); return; }
    e.preventDefault();
    var fd = new FormData(form);
    fetch(form.getAttribute('action') + '?ajax=1', {
      method:'POST', body:fd, headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}
    }).then(function(r){ return r.json(); }).then(function(data){
      if(data && data.ok){ toast('Děkujeme, odesláno. Ozveme se co nejdříve.', true); form.reset(); }
      else { toast('Nepodařilo se odeslat. Zkuste to prosím znovu.', false); }
    }).catch(function(){ toast('Chyba spojení. Zkuste to prosím znovu.', false); });
  });
})();