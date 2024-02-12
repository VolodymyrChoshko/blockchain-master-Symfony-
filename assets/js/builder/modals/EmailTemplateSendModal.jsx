import React, { useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import api from 'lib/api';
import router from 'lib/router';
import { useUIActions } from 'builder/actions/uiActions';
import { Modal, Button, Loading } from 'components';
import { Input, Widget } from 'components/forms';

const EmailTemplateSendModal = ({ id, location, content, emaId, subject, variables, closeModal, ...props }) => {
  const uiActions = useUIActions();
  const [vars, setVars] = useState([]);
  const [values, setValues] = useState({});
  const [email, setEmail] = useState('');
  const [isLoading, setLoading] = useState(false);

  useEffect(() => {
    const _vars = [];
    variables.split(',').forEach((variable) => {
      if (variable !== 'urlUnsubscribe') {
        _vars.push(variable);
      }
    });
    setVars(_vars);

    const _values = {};
    _vars.forEach((variable) => {
      if (variable !== 'urlUnsubscribe') {
        _values[variable] = '';
      }
    });
    setValues(_values);
  }, []);

  /**
   * @param e
   */
  const handleChange = (e) => {
    const newValues = { ...values };
    newValues[e.target.name] = e.target.value;
    setValues(newValues);
  };

  /**
   *
   */
  const handleSubmit = () => {
    if (!email) {
      uiActions.alert('Error', 'Email address required');
      return;
    }

    const body = {
      email,
      location,
      content,
      emaId,
      subject,
      variables: values
    };

    setLoading(true);
    api.post(router.generate('admin_email_template_test', { id }), body)
      .finally(() => {
        setLoading(false);
        uiActions.notice('success', 'Email sent.');
        closeModal();
      });
  };

  return (
    <Modal title="Send Test Email" {...props} auto>
      {isLoading && <Loading />}
      <ul className="list-style-none">
        {vars.map(variable => (
          <li key={variable}>
            <Widget
              label={`{{ ${variable} }}`}
              htmlFor={`variable-${variable}`}
            >
              <Input
                name={variable}
                id={`variable-${variable}`}
                value={values[variable]}
                onChange={handleChange}
              />
            </Widget>
          </li>
        ))}
      </ul>

      <Widget
        label="Email Address"
        htmlFor="input-email"
      >
        <Input
          id="input-email"
          value={email}
          onChange={e => setEmail(e.target.value)}
        />
      </Widget>
      <Button variant="main" onClick={handleSubmit}>Send</Button>
    </Modal>
  );
};

EmailTemplateSendModal.propTypes = {
  id:        PropTypes.number.isRequired,
  location:  PropTypes.string.isRequired,
  content:   PropTypes.string.isRequired,
  emaId:     PropTypes.string.isRequired,
  subject:   PropTypes.string.isRequired,
  variables: PropTypes.string.isRequired
};

export default EmailTemplateSendModal;
