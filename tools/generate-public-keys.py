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


def generate_keys():
    public_keys = []
    for i in range(NUM_KEYS):
        key_type = random.choice(["rsa", "ed25519", "ecdsa"])
        if key_type == "rsa":
            print("generating rsa key...")
            private_key = generate_rsa_key()
        elif key_type == "ed25519":
            print("generating ed25519 key...")
            private_key = generate_ed25519_key()
        elif key_type == "ecdsa":
            print("generating ecdsa key...")
            private_key = generate_ecdsa_key()
        else:
            raise RuntimeError("invalid key_type")

        public_key = get_public_key_openssh(private_key)
        public_keys.append(public_key)
        print(f"completed {i} / {NUM_KEYS}")

    return public_keys


def main():
    public_keys = generate_keys()
    with open("public-keys.json", "w") as f:
        json.dump(public_keys, f, indent=2)

    print(f"{NUM_KEYS} public keys have been generated and saved to public-keys.json")


if __name__ == "__main__":
    main()
