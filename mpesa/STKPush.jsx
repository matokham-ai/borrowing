import React, { useState } from "react";
import { router } from "@inertiajs/react";
import axios from "axios";

export default function STKPush({ onSuccess }) {
    const [form, setForm] = useState({ phone: "", amount: "", account: "" });
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState(null);
    const [error, setError] = useState(null);

    const handleChange = (e) => {
        setForm({ ...form, [e.target.name]: e.target.value });
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setLoading(true);
        setMessage(null);
        setError(null);

        try {
            const res = await axios.post("/mpesa/stk-push", form);
            const data = res.data;

            if (data.ResponseCode === "0") {
                setMessage("STK push sent. Check your phone to complete payment.");
                if (onSuccess) onSuccess(data.CheckoutRequestID);
            } else {
                setError(data.errorMessage || data.ResponseDescription || "Failed to initiate payment.");
            }
        } catch (err) {
            setError(err.response?.data?.message || "An error occurred. Please try again.");
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6 max-w-md w-full">
            <h2 className="text-xl font-semibold text-gray-800 dark:text-gray-100 mb-4">
                Pay via M-Pesa
            </h2>

            {message && (
                <div className="mb-4 p-3 bg-green-100 text-green-800 rounded text-sm">
                    {message}
                </div>
            )}
            {error && (
                <div className="mb-4 p-3 bg-red-100 text-red-800 rounded text-sm">
                    {error}
                </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Phone Number (254XXXXXXXXX)
                    </label>
                    <input
                        type="tel"
                        name="phone"
                        value={form.phone}
                        onChange={handleChange}
                        placeholder="254712345678"
                        required
                        className="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 text-sm dark:bg-gray-700 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-green-500"
                    />
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Amount (KES)
                    </label>
                    <input
                        type="number"
                        name="amount"
                        value={form.amount}
                        onChange={handleChange}
                        placeholder="100"
                        min="1"
                        required
                        className="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 text-sm dark:bg-gray-700 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-green-500"
                    />
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Account Reference
                    </label>
                    <input
                        type="text"
                        name="account"
                        value={form.account}
                        onChange={handleChange}
                        placeholder="e.g. Registration Fee"
                        required
                        className="w-full border border-gray-300 dark:border-gray-600 rounded px-3 py-2 text-sm dark:bg-gray-700 dark:text-gray-100 focus:outline-none focus:ring-2 focus:ring-green-500"
                    />
                </div>

                <button
                    type="submit"
                    disabled={loading}
                    className="w-full bg-green-600 hover:bg-green-700 disabled:opacity-50 text-white font-medium py-2 px-4 rounded transition"
                >
                    {loading ? "Sending STK Push..." : "Pay Now"}
                </button>
            </form>
        </div>
    );
}
