import json
import random
from cryptography.hazmat.primitives import serialization
from cryptography.hazmat.primitives.asymmetric import rsa, ed25519, ec

RSA_MIN_BITS = 1024
# RSA_MAX_BITS = 16384
# ECDSA_BIT_SIZES = [256, 384, 521]
# ED25519_MIN_BITS = 1
# ED25519_MAX_BITS = 4294967295  # 32 bit unsigned int max - 1
NUM_KEYS = 100


def generate_rsa_key():
    # key_size = random.randint(RSA_MIN_BITS, RSA_MAX_BITS)
    key_size = RSA_MIN_BITS
    private_key = rsa.generate_private_key(public_exponent=65537, key_size=key_size)
    return private_key


def generate_ed25519_key():
    private_key = ed25519.Ed25519PrivateKey.generate()
    return private_key


def generate_ecdsa_key():
    curve = random.choice([ec.SECP256R1(), ec.SECP384R1(), ec.SECP521R1()])
    private_key = ec.generate_private_key(curve)
    return private_key


def get_public_key_openssh(private_key):
    public_key = private_key.public_key()
    return public_key.public_bytes(
        encoding=serialization.Encoding.OpenSSH, format=serialization.PublicFormat.OpenSSH
    ).decode()


key_funcs = random.choices([generate_rsa_key, generate_ed25519_key, generate_ecdsa_key], k=NUM_KEYS)
private_keys = [func() for func in key_funcs]
public_keys = [get_public_key_openssh(x) for x in private_keys]
print(json.dumps(public_keys))
