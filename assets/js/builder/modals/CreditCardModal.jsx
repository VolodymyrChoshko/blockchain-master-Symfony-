import React, { useEffect, useState } from 'react';
import creditCardType from 'credit-card-type';
import { useSelector } from 'react-redux';
import { Modal, Button, Flex } from 'components';
import { Input, Select, Widget } from 'components/forms';
import { loading } from 'utils';
import api from 'lib/api';
import router from 'lib/router';

const currentYear = (new Date()).getFullYear();
const months = [
  { label: '(01) January', value: 1 },
  { label: '(02) February', value: 2 },
  { label: '(03) March', value: 3 },
  { label: '(04) April', value: 4 },
  { label: '(05) May', value: 5 },
  { label: '(06) June', value: 6 },
  { label: '(07) July', value: 7 },
  { label: '(08) August', value: 8 },
  { label: '(09) September', value: 9 },
  { label: '(10) October', value: 10 },
  { label: '(11) November', value: 11 },
  { label: '(12) December', value: 12 }
];

const years = [];
for (let i = currentYear; i < (currentYear + 10); i++) {
  years.push({ label: i, value: i });
}

/**
 * @param oField
 * @returns {number}
 */
const doGetCaretPosition = (oField) => {
  let iCaretPos = 0;

  // IE Support
  if (document.selection) {
    oField.focus();
    const oSel = document.selection.createRange();
    oSel.moveStart('character', -oField.value.length);
    iCaretPos = oSel.text.length;
  } else if (oField.selectionStart || oField.selectionStart === 0) {
    iCaretPos = oField.selectionDirection === 'backward' ? oField.selectionStart : oField.selectionEnd;
  }

  return iCaretPos;
};

const CreditCardModal = ({ closeModal, onComplete, ...props }) => {
  const stripePublicKey = useSelector(state => state.billing.stripePublicKey);
  if (!stripePublicKey) {
    console.error('stripePublicKey not set');
    return <div />;
  }

  const [number, setNumber] = useState('');
  const [expMonth, setExpMonty] = useState(1);
  const [expYear, setExpYear] = useState(currentYear);
  const [securityCode, setSecurityCode] = useState('');
  const [cardType, setCardType] = useState({
    type: '',
    code: { size: 3 }
  });
  const [cardImage, setCardImage] = useState('/assets/images/cards/generic.svg');
  const [error, setError] = useState('');
  const [errors, setErrors] = useState({
    number:       false,
    securityCode: false,
  });

  useEffect(() => {
    Stripe.setPublishableKey(stripePublicKey);
  }, []);

  /**
   * @param status
   * @param response
   */
  const handleStripeUpdate = (status, response) => {
    if (response.error) {
      setError(response.error.message);
    } else {
      api.post(router.generate('billing_cc_update'), { token: response.id })
        .then((resp) => {
          if (resp.error) {
            setError(resp.error);
          } else {
            closeModal();
            if (onComplete) {
              onComplete();
            } else {
              document.location.reload();
            }
          }
        })
        .finally(() => {
          loading(false);
        });
    }
  };

  /**
   *
   */
  const handleSubmit = () => {
    setError('');
    setErrors({ number: false, securityCode: false });

    if (!number) {
      setErrors({ number: true, securityCode: false });
      return setError('Credit card number required.');
    }
    if (!securityCode) {
      setErrors({ number: false, securityCode: true });
      return setError('Security code required.');
    }

    loading(true);
    Stripe.card.createToken({
      number,
      exp_month: expMonth,
      exp_year:  expYear,
      cvc:       securityCode,
    }, handleStripeUpdate);
    return null;
  };

  /**
   *
   * @param num
   */
  const handleChange = (num) => {
    const clean = num.replace(/-/g, '');
    const card  = creditCardType(clean);

    if (clean && card && card.length > 0) {
      setCardType(card[0]);
      switch (card[0].type) {
        case 'visa':
          setCardImage('/assets/images/cards/visa.svg');
          break;
        case 'american-express':
          setCardImage('/assets/images/cards/amex.svg');
          break;
        case 'discover':
          setCardImage('/assets/images/cards/discover.svg');
          break;
        case 'jcb':
          setCardImage('/assets/images/cards/jcb.svg');
          break;
        case 'mastercard':
          setCardImage('/assets/images/cards/mastercard.svg');
          break;
        case 'maestro':
          setCardImage('/assets/images/cards/maestro.svg');
          break;
        default:
          setCardImage('/assets/images/cards/generic.svg');
          break;
      }
    } else {
      setCardType({
        type: '',
        code: { size: 3 }
      });
      setCardImage('/assets/images/cards/generic.svg');
    }
  };

  /**
   * @param e
   */
  const handleNumberChange = (e) => {
    const { value } = e.target;

    let newValue = value.trim();
    if ((newValue.replace(/-/g, '')).length % 4 === 0 && newValue[newValue.length - 1] !== '-') {
      newValue = `${newValue}-`;
      if (newValue === '-') {
        newValue = '';
      }
    }
    if (newValue.length > 19) {
      newValue = newValue.slice(0, 19);
    }
    setNumber(newValue);
    handleChange(newValue);
  };

  /**
   * @param e
   */
  const handleNumberKeyUp = (e) => {
    if ((e.key && e.key === 'Backspace') || (e.keyCode && e.keyCode === 8)) {
      const pos = doGetCaretPosition(e.target);
      if (pos === 0) {
        setNumber('');
        handleChange('');
      } else if (number[pos - 1] === '-') {
        setNumber(number.slice(0, number.length - 1));
        handleChange(number.slice(0, number.length - 1));
      }
    }
  };

  /**
   * @param e
   */
  const handleNumberPaste = (e) => {
    e.preventDefault();

    const text = e.clipboardData.getData('Text').replace(/-/g, '');
    let newValue = '';
    for (let i = 0; i < text.length; i++) {
      if (i !== 0 && i % 4 === 0) {
        newValue += '-';
      }
      newValue += text[i];
    }
    setNumber(newValue);
    handleChange(newValue);
  };

  return (
    <Modal title="Add Credit Card" {...props} flexStart auto sm>
      {error && (
        <div className="bg-danger text-light rounded-normal p-2 mb-2" style={{ fontSize: '1rem' }}>
          {error}
        </div>
      )}
      <Flex>
        <Widget
          label="Number"
          htmlFor="credit-card-number"
          className="w-100 mr-2 position-relative"
          error={errors.number}
        >
          <Input
            name="number"
            value={number}
            type="tel"
            inputMode="numeric"
            pattern="[0-9\s]{13,19}"
            id="credit-card-number"
            autoComplete="cc-number"
            maxLength="19"
            placeholder="xxxx-xxxx-xxxx-xxxx"
            onPaste={handleNumberPaste}
            onChange={handleNumberChange}
            onKeyUp={handleNumberKeyUp}
            required
            autoFocus
          />
          <img src={cardImage} alt="" style={{ height: 23, position: 'absolute', top: 31, right: 8 }} />
        </Widget>
        <Widget
          label="Security Code"
          htmlFor="credit-card-security-code"
          error={errors.securityCode}
          style={{ width: 140 }}
        >
          <Input
            size={cardType.code.size}
            name="securityCode"
            value={securityCode}
            maxLength={cardType.code.size}
            id="credit-card-security-code"
            placeholder={'•'.repeat(cardType.code.size)}
            onChange={e => setSecurityCode(e.target.value)}
            required
          />
        </Widget>
      </Flex>
      <Flex>
        <Widget label="Expires Month" htmlFor="credit-expires-month" className="w-100 mr-2">
          <Select
            name="expMonth"
            value={expMonth}
            id="credit-expires-month"
            options={months}
            onChange={e => setExpMonty(e.target.value)}
            required
          />
        </Widget>
        <Widget label="Expires Year" htmlFor="credit-expires-year" className="w-100">
          <Select
            name="expYear"
            value={expYear}
            id="credit-expires-year"
            options={years}
            onChange={e => setExpYear(e.target.value)}
            required
          />
        </Widget>
      </Flex>
      <Button variant="main" onClick={handleSubmit}>
        Save Card
      </Button>
    </Modal>
  );
};

export default CreditCardModal;
