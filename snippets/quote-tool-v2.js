(function () {
  var mount = document.getElementById('tl-quote-tool');
  if (!mount) return;

  // ─────────────────────────────────────────────────────────
  // 가격 정책 단일 소스 — 선택 화면 표시, 실제 계산, 결과 설명이
  // 전부 이 PRICING 객체 하나만 읽습니다. 가격을 바꿀 땐 여기만 고치면 됩니다.
  // ─────────────────────────────────────────────────────────
  var PRICING = {
    services: [
      {
        id: 'single_page',
        label: '한 페이지로 서비스·상품을 소개하고 싶어요',
        desc: '이벤트, 상품, 개인 서비스 등을 한 페이지에 소개하고 문의·신청을 받는 형태',
        hint: '랜딩페이지',
        base: 200000,
        rangeWidth: 'narrow',
        days: 5
      },
      {
        id: 'company_site',
        label: '회사·가게·브랜드 홈페이지가 필요해요',
        desc: '소개, 서비스, 위치, 문의 등 여러 정보를 체계적으로 보여주는 소규모 홈페이지',
        hint: '소개 사이트',
        base: 300000,
        rangeWidth: 'medium',
        days: 6
      },
      {
        id: 'webapp',
        label: '예약·신청·회원 기능 등이 있는 서비스를 만들고 싶어요',
        desc: '사용자가 직접 입력하고 이용하는 간단한 웹서비스',
        hint: '웹서비스',
        base: 400000,
        rangeWidth: 'wide',
        days: 8
      },
      {
        id: 'improve',
        label: '이미 있는 사이트를 고치고 싶어요',
        desc: '디자인 개선, 모바일 대응, 일부 기능 추가·수정 등',
        hint: '사이트 개선',
        base: 200000,
        rangeWidth: 'medium',
        days: 4
      },
      {
        id: 'automation',
        label: '반복 업무를 자동화하고 싶어요',
        desc: '자료 정리, 데이터 처리 등 반복 작업을 줄이는 간단한 자동화',
        hint: '업무 자동화',
        base: 250000,
        rangeWidth: 'medium',
        days: 4
      }
    ],

    // 서비스 유형별 "요구사항 불확실성" 범위. base(시작가)를 low로 쓰고,
    // high는 base에 이 배율을 곱해 만듭니다 — 유형마다 폭을 다르게 둡니다.
    rangeFactors: { narrow: 1.10, medium: 1.20, wide: 1.35 },

    features: [
      {
        id: 'booking', label: '예약이나 신청을 받고 싶어요', hint: '예약·신청 기능',
        min: 100000, max: 150000, days: 2
      },
      {
        id: 'login', label: '회원가입하고 로그인해서 쓰는 서비스예요', hint: '회원 로그인',
        min: 100000, max: 150000, days: 2
      },
      {
        id: 'admin', label: '신청 내역이나 등록된 정보를 직접 확인·관리하고 싶어요', hint: '운영자 관리 화면',
        min: 150000, max: 200000, days: 2
      },
      {
        id: 'i18n', label: '외국어로도 보여주고 싶어요', hint: '다국어 지원',
        min: 100000, max: 200000, days: 2
      },
      {
        id: 'integration', label: '현재 사용 중인 다른 서비스와 연결하고 싶어요', hint: '외부 서비스·API 연동',
        min: 100000, max: 250000, days: 3,
        consult: true,
        consultNote: '연동 대상에 따라 범위 차이가 커서, 정확한 비용과 기간은 상담 후 산정합니다.'
      }
    ],

    // 명시적으로 확인된 조합에만 적용하는 묶음 최적화 규칙.
    // 전체 조합에 일괄 할인을 적용하지 않습니다 — 여기 없는 조합은 단순 합산됩니다.
    bundles: [
      {
        ids: ['booking', 'login', 'admin'],
        label: '예약·신청 + 회원 로그인 + 운영자 관리 화면',
        reason: '로그인 인증, 신청자 정보 구조, 운영자 권한을 함께 설계하면 각 기능을 따로 만들 때보다 중복 구현을 줄일 수 있습니다.',
        saveMin: 100000,
        saveMax: 150000,
        saveDays: 2
      }
    ],

    // 정상 견적 계산식과는 완전히 분리된 별도 정책. 계산에 섞이지 않습니다.
    promo: {
      enabled: true,
      text: 'Tenlune 초기 포트폴리오 확보 프로모션 — 가장 작은 작업은 150,000원부터 진행할 수 있습니다 (위 예상 견적과 별개이며, 상담 시 안내드립니다).'
    },

    budgets: [
      { id: 'na', label: '아직 미정', value: null },
      { id: 'b1', label: '50만원 이하', value: 500000 },
      { id: 'b2', label: '50만원 ~ 100만원', value: 1000000 },
      { id: 'b3', label: '100만원 이상', value: null }
    ]
  };

  function el(tag, attrs, children) {
    var e = document.createElement(tag);
    attrs = attrs || {};
    for (var k in attrs) {
      if (k === 'class') e.className = attrs[k];
      else if (k === 'text') e.textContent = attrs[k];
      else e.setAttribute(k, attrs[k]);
    }
    (children || []).forEach(function (c) { if (c) e.appendChild(c); });
    return e;
  }

  function fmt(n) { return n.toLocaleString('ko-KR') + '원'; }
  function round10k(n) { return Math.round(n / 10000) * 10000; }
  function allTrue(list) { return list.every(function (v) { return v; }); }

  function serviceRange(service) {
    var factor = PRICING.rangeFactors[service.rangeWidth] || 1.15;
    return { low: service.base, high: round10k(service.base * factor) };
  }

  function radioGroup(name, options, defaultId, describe) {
    var wrap = el('div', { class: 'tl-quote-options tl-quote-options-col' });
    options.forEach(function (opt) {
      var id = name + '-' + opt.id;
      var input = el('input', { type: 'radio', name: name, id: id, value: opt.id });
      if (opt.id === defaultId) input.checked = true;
      var textParts = [el('span', { class: 'tl-quote-opt-label', text: opt.label })];
      var extra = describe ? describe(opt) : null;
      if (extra) textParts.push(el('span', { class: 'tl-quote-opt-sub', text: extra }));
      var label = el('label', { for: id, class: 'tl-quote-opt tl-quote-opt-block' }, [input].concat(textParts));
      wrap.appendChild(label);
    });
    return wrap;
  }

  function checkGroup(name, options, describe) {
    var wrap = el('div', { class: 'tl-quote-options tl-quote-options-col' });
    options.forEach(function (opt) {
      var id = name + '-' + opt.id;
      var input = el('input', { type: 'checkbox', name: name, id: id, value: opt.id });
      var textParts = [el('span', { class: 'tl-quote-opt-label', text: opt.label })];
      var extra = describe ? describe(opt) : null;
      if (extra) textParts.push(el('span', { class: 'tl-quote-opt-sub', text: extra }));
      var label = el('label', { for: id, class: 'tl-quote-opt tl-quote-opt-block' }, [input].concat(textParts));
      wrap.appendChild(label);
    });
    return wrap;
  }

  var form = el('div', { class: 'tl-quote-form' }, [
    el('div', { class: 'tl-quote-q' }, [
      el('p', { class: 'tl-quote-label', text: '1. 무엇을 만들고 싶으신가요?' }),
      radioGroup('service', PRICING.services, 'single_page', function (s) {
        var r = serviceRange(s);
        return s.desc + ' — ' + fmt(r.low) + '부터 (' + s.hint + ')';
      })
    ]),
    el('div', { class: 'tl-quote-q' }, [
      el('p', { class: 'tl-quote-label', text: '2. 필요한 기능이 있다면 모두 선택해 주세요 (선택 사항)' }),
      checkGroup('feat', PRICING.features, function (f) {
        if (f.consult) return '(' + f.hint + ') 상담 후 산정';
        return '(' + f.hint + ') 예상 +' + fmt(f.min) + ' ~ ' + fmt(f.max);
      })
    ]),
    el('div', { class: 'tl-quote-q' }, [
      el('p', { class: 'tl-quote-label', text: '3. 예산은 어느 정도로 생각하고 계신가요?' }),
      radioGroup('budget', PRICING.budgets, 'na')
    ])
  ]);

  var submitBtn = el('button', { type: 'button', class: 'tl-quote-submit', text: '예상 구성 확인하기' });
  var resultBox = el('div', { class: 'tl-quote-result', style: 'display:none' });

  var promoNote = null;
  if (PRICING.promo.enabled) {
    promoNote = el('p', { class: 'tl-quote-promo', text: PRICING.promo.text });
  }

  function computePackage(service, feats) {
    var consultFeats = feats.filter(function (f) { return f.consult; });
    var priced = feats.filter(function (f) { return !f.consult; });

    var base = serviceRange(service);
    var featLow = priced.reduce(function (s, f) { return s + f.min; }, 0);
    var featHigh = priced.reduce(function (s, f) { return s + f.max; }, 0);

    var featIds = priced.map(function (f) { return f.id; });
    var bundle = PRICING.bundles.filter(function (b) {
      return b.ids.every(function (id) { return featIds.indexOf(id) !== -1; });
    })[0] || null;

    var preLow = base.low + featLow;
    var preHigh = base.high + featHigh;
    var saveMin = bundle ? bundle.saveMin : 0;
    var saveMax = bundle ? bundle.saveMax : 0;
    var low = round10k(Math.max(0, preLow - saveMin));
    var high = round10k(Math.max(low, preHigh - saveMax));

    var days = service.days
      + priced.reduce(function (s, f) { return s + f.days; }, 0)
      + consultFeats.reduce(function (s, f) { return s + f.days; }, 0)
      - (bundle ? bundle.saveDays : 0);
    if (days < service.days) days = service.days;

    return {
      service: service, feats: feats, priced: priced, consultFeats: consultFeats,
      base: base, featLow: featLow, featHigh: featHigh, bundle: bundle,
      preLow: preLow, preHigh: preHigh, low: low, high: high, days: days
    };
  }

  function renderPackageLines(container, pack) {
    var lines = el('ul', { class: 'tl-quote-summary' });
    lines.appendChild(el('li', { text: pack.service.label + ' — ' + fmt(pack.base.low) + ' ~ ' + fmt(pack.base.high) }));
    pack.priced.forEach(function (f) {
      lines.appendChild(el('li', { text: '+ ' + f.label + ' — +' + fmt(f.min) + ' ~ +' + fmt(f.max) }));
    });
    pack.consultFeats.forEach(function (f) {
      lines.appendChild(el('li', { text: '+ ' + f.label + ' — 상담 후 산정' }));
    });
    container.appendChild(lines);
  }

  function compute() {
    var serviceId = (form.querySelector('input[name="service"]:checked') || {}).value || 'single_page';
    var budgetId = (form.querySelector('input[name="budget"]:checked') || {}).value || 'na';
    var featIds = Array.prototype.map.call(form.querySelectorAll('input[name="feat"]:checked'), function (i) { return i.value; });

    var service = PRICING.services.filter(function (s) { return s.id === serviceId; })[0];
    var budget = PRICING.budgets.filter(function (b) { return b.id === budgetId; })[0];
    var feats = PRICING.features.filter(function (f) { return featIds.indexOf(f.id) !== -1; });

    var pack = computePackage(service, feats);

    resultBox.innerHTML = '';

    resultBox.appendChild(el('p', { class: 'tl-quote-result-title', text: '개별 구성 기준 예상 비용' }));
    renderPackageLines(resultBox, pack);

    if (pack.bundle) {
      var bundleBox = el('div', { class: 'tl-quote-bundle' });
      bundleBox.appendChild(el('p', { class: 'tl-quote-notes-title', text: '공유 가능한 구현 작업' }));
      bundleBox.appendChild(el('p', { text: pack.bundle.label + ' — ' + pack.bundle.reason }));
      bundleBox.appendChild(el('p', { class: 'tl-quote-bundle-save', text: '중복 구현 감소: 약 -' + fmt(pack.bundle.saveMin) + ' ~ -' + fmt(pack.bundle.saveMax) }));
      resultBox.appendChild(bundleBox);
    }

    var finalBox = el('div', { class: 'tl-quote-final' });
    finalBox.appendChild(el('p', { class: 'tl-quote-result-title', text: (pack.bundle ? '최적화 후 ' : '') + '예상 견적' }));
    finalBox.appendChild(el('p', { class: 'tl-quote-final-price', text: fmt(pack.low) + ' ~ ' + fmt(pack.high) }));
    finalBox.appendChild(el('p', { class: 'tl-quote-final-days', text: '예상 기간: 약 ' + pack.days + '영업일 내외' }));
    finalBox.appendChild(el('p', { class: 'tl-quote-disclaimer', text: '실제 견적은 상담 후 확정됩니다. 위 금액은 확정 견적이 아닌 예상 범위입니다.' }));
    resultBox.appendChild(finalBox);

    if (pack.consultFeats.length) {
      var consultBox = el('div', { class: 'tl-quote-notes' });
      pack.consultFeats.forEach(function (f) {
        consultBox.appendChild(el('p', { text: f.label + ' — ' + f.consultNote }));
      });
      resultBox.appendChild(consultBox);
    }

    // 예산 대비 대안 — 가격을 억지로 낮추지 않고, 단계를 나누어 제안합니다.
    if (budget.value) {
      if (pack.low > budget.value) {
        var kept = pack.feats.slice();
        var deferred = [];
        var altPack = computePackage(service, kept);
        var guard = 0;
        while (allTrue([altPack.low > budget.value, kept.length > 0, guard < 10])) {
          var priced = kept.filter(function (f) { return !f.consult; });
          var target = priced.length ? priced : kept;
          target.sort(function (a, b) { return b.max - a.max; });
          var drop = target[0];
          kept = kept.filter(function (f) { return f.id !== drop.id; });
          deferred.push(drop);
          altPack = computePackage(service, kept);
          guard++;
        }

        var altBox = el('div', { class: 'tl-quote-budget-alt' });
        altBox.appendChild(el('p', { class: 'tl-quote-notes-title', text: '예산에 맞춘 대안' }));

        if (allTrue([deferred.length > 0, altPack.low <= budget.value])) {
          altBox.appendChild(el('p', { text: '입력하신 예산보다 예상 견적이 높습니다. 아래처럼 단계를 나누면 예산 안에서 먼저 시작할 수 있습니다.' }));
          altBox.appendChild(el('p', { class: 'tl-quote-phase-title', text: '1단계 (지금 진행) — ' + fmt(altPack.low) + ' ~ ' + fmt(altPack.high) }));
          renderPackageLines(altBox, altPack);
          var deferredList = el('p', {
            class: 'tl-quote-phase-title',
            text: '2단계 (예산이 늘어나면 추가 가능): ' + deferred.map(function (f) { return f.label; }).join(', ')
          });
          altBox.appendChild(deferredList);
        } else {
          altBox.appendChild(el('p', { text: '선택하신 서비스의 기본 구성 자체가 입력하신 예산보다 높습니다. 범위를 더 줄인 형태(예: 한 페이지로 소개하기)로 시작하는 방향도 있으니 문의 시 함께 상담해 드립니다.' }));
        }
        resultBox.appendChild(altBox);
      }
    }

    if (promoNote) {
      resultBox.appendChild(el('p', { class: 'tl-quote-promo', text: PRICING.promo.text }));
    }

    var aiBox = el('div', { class: 'tl-quote-ai', style: 'display:none' });
    resultBox.appendChild(aiBox);

    var summaryText = [
      pack.service.label + ' (' + pack.service.hint + ')',
      pack.priced.length ? '필요 기능: ' + pack.priced.map(function (f) { return f.hint; }).join(', ') : '추가 기능 없음',
      pack.consultFeats.length ? '상담 필요 항목: ' + pack.consultFeats.map(function (f) { return f.hint; }).join(', ') : '',
      '예상 견적: ' + fmt(pack.low) + ' ~ ' + fmt(pack.high) + ' (참고용, 정확한 견적은 상담 후 확정)',
      '예상 기간: 약 ' + pack.days + '영업일 내외',
      '예산: ' + budget.label
    ].filter(function (s) { return s; }).join('\n');

    var contactUrl = '/contact/?prefill=' + encodeURIComponent('[AI 견적 최적화에서 넘어옴]\n' + summaryText + '\n\n추가로 전달하고 싶은 내용:\n');
    var cta = el('a', { href: contactUrl, class: 'wp-block-button__link wp-element-button', text: '이 구성으로 문의하기 →' });
    resultBox.appendChild(el('div', { class: 'wp-block-button tl-quote-cta' }, [cta]));

    resultBox.style.display = '';
    if (typeof resultBox.scrollIntoView === 'function') {
      try { resultBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); } catch (e) { /* 구형 환경 무시 */ }
    }

    if (window.tlAiQuoteEndpoint) {
      fetch(window.tlAiQuoteEndpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          service: pack.service.label,
          features: pack.priced.map(function (f) { return f.label; }),
          consultFeatures: pack.consultFeats.map(function (f) { return f.label; }),
          bundle: pack.bundle ? pack.bundle.label : null,
          low: pack.low, high: pack.high, days: pack.days, budget: budget.label
        })
      }).then(function (r) { return r.ok ? r.json() : null; }).then(function (data) {
        if (data) {
          if (data.explanation) {
            aiBox.innerHTML = '';
            aiBox.appendChild(el('p', { class: 'tl-quote-ai-title', text: 'AI 요약' }));
            aiBox.appendChild(el('p', { class: 'tl-quote-ai-text', text: data.explanation }));
            aiBox.style.display = '';
          }
        }
      }).catch(function () { /* 조용히 무시 — 규칙 기반 결과는 이미 표시됨 */ });
    }
  }

  submitBtn.addEventListener('click', compute);

  mount.innerHTML = '';
  mount.appendChild(form);
  if (promoNote) mount.appendChild(promoNote);
  mount.appendChild(submitBtn);
  mount.appendChild(resultBox);
})();
