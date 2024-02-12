import io from 'socket.io-client';

$(() => {
  if (!window.socket) {
    return;
  }

  const socket = io(window.socket.url, {
    path: window.socket.path
  });

  socket.on('connect', () => {
    socket.on('listUsers', (msg) => {
      const { room, users } = msg;
      if (users.length > 0) {
        const $btns = $(`.row-email[data-room="${room}"] .row-email-buttons`);
        const $btn  = $('<button class="btn btn-alt btn-sm btn-room-kick">Kick</button>');
        $btn.on('click', () => {
          socket.emit('kick', room);
          $btn.remove();
        });
        $btns.prepend($btn);
      }
    });

    $('.btn-room-kick').remove();
    $('.row-email').each((i, el) => {
      const $target = $(el);
      socket.emit('listUsers', $target.data('room'));
    });
  });
});

$(() => {
  let results = [];

  const $autoCompletes = $('.auto-complete-users');
  $autoCompletes.on('typeahead:select', (e) => {
    const email = e.target.value;
    const nameTarget = e.target.getAttribute('data-users-name-target');
    if (nameTarget) {
      const $name = $(nameTarget);
      results.forEach((result) => {
        if (result.email === email) {
          $name.val(result.name);
        }
      });
    }
  });

  $autoCompletes.typeahead({
      hint:      true,
      highlight: true,
      minLength: 1
    },
    {
      limit:  12,
      async:  true,
      source: (search, processSync, processAsync) => {
        return $.get('/admin/users/email/match', { search }, (data) => {
          results = data;
          return processAsync(data);
        });
      },
      display: (val) => {
        return val.email;
      }
    });
});

$(() => {
  if ($('.container[data-billing-promotion=1]').length === 0) {
    return;
  }

  const $inputCode         = $('#input-code');
  const $inputType         = $('#input-type');
  const $inputPeriodMonths = $('#input-period-months');
  const $inputValue        = $('#input-value');
  const $inputValueType    = $('#input-value-type');
  const $inputIsNewUser    = $('#input-is-new-user');
  const $inputIsTeamPlan   = $('#input-is-team-plan');
  const $inputTargets      = $('.form-control[data-target]');
  const $inputMemberships  = $('.input-target-membership');
  const $inputDescription  = $('#input-description');
  let isDescriptionChanged = false;

  const description = {
    code:         $inputCode.val(),
    type:         $inputType.val(),
    periodMonths: parseInt($inputPeriodMonths.val(), 10),
    value:        $inputValue.val(),
    valueType:    $inputValueType.val(),
    isNewUser:    $inputIsNewUser.is(':checked'),
    isTeamPlan:   $inputIsTeamPlan.is(':checked'),
    amountCents:  0,
    targets:      [],
    toString:     () => {
      const parts = [];

      if (description.isNewUser) {
        parts.push('new users');
        if (description.isTeamPlan) {
          parts.push('with a Blocks Edit Team plan');
        }
      } else if (description.isTeamPlan) {
        parts.push('Blocks Edit Team organizations');
      }

      if (description.code) {
        parts.push(`use promo code "${description.code}"`);
      }

      if (description.value && description.type === 'discount') {
        if (description.valueType === 'dollar') {
          parts.push(`to receive $${description.value} off`);
        } else {
          parts.push(`to receive ${parseInt(description.value, 10)}% off`);
        }
      }

      const target = description.targets.join(' + ');
      if (target) {
        if (description.value && description.type === 'fixed_dollar') {
          parts.push('to receive');
        }

        parts.push(target);
        if (description.periodMonths === 1) {
          parts.push('for the first month');
        } else if (description.periodMonths !== 0) {
          parts.push(`for ${description.periodMonths} months`);
        } else if (description.periodMonths === 0) {
          parts.push('every month');
        }
      }

      if (description.value && description.type === 'fixed') {
        parts.push(`for only $${description.value}`);
        if (description.periodMonths !== 1) {
          parts.push('a month');
        }
      }

      if (description.amountCents > 0) {
        let amountOff = description.amountCents;

        if (description.value && description.type === 'discount') {
          if (description.valueType === 'dollar') {
            amountOff     = (parseInt(description.value.replace('.', ''), 10));
            const savings = Math.floor((amountOff / description.amountCents) * 100);
            parts.push('.');
            parts.push(`Saving ${savings}%`);
          } else {
            const percent = parseInt(description.value, 10) / 100;
            amountOff    -= (description.amountCents * percent);
            const savings = (description.amountCents - amountOff) / 100;
            parts.push('.');
            parts.push(`Saving $${savings.toFixed(2)}`);
          }
        } else if (description.value && description.type === 'fixed') {
          const amount = (parseInt(description.value.replace('.', ''), 10));
          amountOff -= amount;
          const savings = (amountOff / 100);
          parts.push('.');
          parts.push(`Saving $${savings.toFixed(2)}`);
        }

        if (description.periodMonths !== 1) {
          parts.push('a month');
        }
      }

      const str = parts.join(' ');
      if (str.length > 0) {
        return `${str[0].toUpperCase()}${str.substr(1).replace(' .', '.')}.`;
      }

      return str;
    }
  };

  if ($inputDescription.val() === '') {
    $inputDescription.val(description.toString());
  } else {
    isDescriptionChanged = true;
  }

  $inputType.on('change', () => {
    if ($inputType.val() === 'fixed') {
      $inputValueType.val('dollar').prop('disabled', true);
    } else {
      $inputValueType.prop('disabled', false);
    }
  });

  $inputCode.on('input', (e) => {
    $inputCode.val(e.target.value.replace(/[^\w\d_-]/ig, ''));
  });

  $inputIsTeamPlan.on('change', () => {
    $inputMemberships.prop('disabled', $inputIsTeamPlan.is(':checked'));
  });

  $inputDescription.on('keydown', () => {
    isDescriptionChanged = true;
  });

  $('.form-control').on('change', (e) => {
    if (isDescriptionChanged) {
      return;
    }

    const $target = $(e.target);
    const { name } = e.target;

    if ($target.is('[data-target]')) {
      const targets = [];
      let amountCents = 0;
      $inputTargets.each((i, el) => {
        if (el.checked) {
          targets.push(el.getAttribute('data-target'));
          amountCents += parseInt(el.getAttribute('data-price'), 10);
        }
      });
      description.targets = targets;
      description.amountCents = amountCents;
    } else if ($target.is('[type="checkbox"]') && description[name] !== undefined) {
      description[name] = e.target.checked;
    } else if (name === 'periodMonths') {
      description.periodMonths = parseInt(e.target.value, 10);
    } else if (description[name] !== undefined) {
      description[name] = e.target.value;
    }

    $inputDescription.val(description.toString());
  });

  $('#form-promo').on('submit', (e) => {
    if (description.targets.length === 0) {
      e.preventDefault();
      window.jAlert('', 'No targets selected.');
    }
  });
});
