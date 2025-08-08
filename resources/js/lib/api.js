export async function signIn(email, password) {
  const res = await fetch('http://localhost:8000/api/sign-in', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password }),
  });
  const data = await res.json();
  if (!res.ok) throw { response: { data } };
  return data;
}

export async function signUp(name, email, password) {
  const res = await fetch('http://localhost:8000/api/sign-up', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ name, email, password }),
  });
  const data = await res.json();
  if (!res.ok) throw { response: { data } };
  return data;
}

