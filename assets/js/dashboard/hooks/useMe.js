import { useSelector } from 'react-redux';

/**
 * @returns {User}
 */
const useMe = () => {
  return useSelector(state => state.users.me);
};

export default useMe;
