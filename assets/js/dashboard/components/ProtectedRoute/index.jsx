import React from 'react';
import PropTypes from 'prop-types';
import useMe from 'dashboard/hooks/useMe';
import { Navigate } from 'react-router-dom';

const ProtectedRoute = ({ children }) => {
  const me = useMe();

  if (!me) {
    return <Navigate to="/login" replace />;
  }

  return children;
};

ProtectedRoute.propTypes = {
  children: PropTypes.node
};

export default ProtectedRoute;
